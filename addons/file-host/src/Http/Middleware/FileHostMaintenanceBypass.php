<?php

/*
 * FileHost Addon for ClientXCMS V2
 * Author: Corentin WebSite
 * Year: 2026
 * License: Open Source
 *
 * Disclaimer: La maintenance de fonctionnement est assurée par Corentin WebSite.
 * En cas de modification du code par un tiers, l'auteur décline toute responsabilité
 * si le logiciel ne fonctionne plus correctement.
 */

namespace App\Addons\FileHost\Http\Middleware;

use App\Addons\FileHost\Models\FileHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileHostMaintenanceBypass
{
    private static ?string $cachedPrefix = null;

    public function handle(Request $request, Closure $next)
    {
        if (self::$cachedPrefix === null) {
            try {
                self::$cachedPrefix = setting('file_host_prefix', 'drive') ?: 'drive';
            } catch (\Throwable $e) {
                self::$cachedPrefix = 'drive';
            }
        }

        $path = ltrim($request->getPathInfo(), '/');
        if (!str_starts_with($path, self::$cachedPrefix . '/')) {
            return $next($request);
        }

        $uuid = substr($path, strlen(self::$cachedPrefix) + 1);
        $uuid = str_replace("\0", '', $uuid);
        
        if (empty($uuid) || str_contains($uuid, '..')) {
            return $next($request);
        }

        try {
            $file = FileHost::where('uuid', $uuid)->first();

            if (!$file || !Storage::exists($file->file_path)) {
                return $next($request);
            }

            $storagePath = realpath(storage_path('app'));
            $filePath    = Storage::path($file->file_path);
            $realPath    = realpath($filePath);

            if ($realPath === false || !str_starts_with($realPath, $storagePath . DIRECTORY_SEPARATOR)) {
                return response(__('file-host::messages.access_denied'), 403);
            }

            $mimeType    = $file->mime_type ?? 'application/octet-stream';
            $disposition = 'inline';
            if (in_array($mimeType, FileHost::DOWNLOAD_ONLY_MIMES, true)) {
                $disposition = 'attachment';
                $mimeType    = 'application/octet-stream';
            }

            $safeName = preg_replace('/[\x00-\x1F\x7F\"\\\]/', '', $file->original_name);
            $safeName = mb_substr(trim($safeName) ?: 'fichier', 0, 255);

            $file->increment('views');

            return response()->file($realPath, [
                'Content-Type'           => $mimeType,
                'Content-Disposition'    => $disposition . '; filename="' . addslashes($safeName) . '"; filename*=UTF-8\'\'' . rawurlencode($safeName),
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
                'Cache-Control'          => 'private, max-age=3600',
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FileHost bypass error: ' . $e->getMessage());
            return $next($request);
        }
    }
}
