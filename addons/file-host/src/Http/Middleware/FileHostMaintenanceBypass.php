<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

namespace App\Addons\FileHost\Http\Middleware;

use App\Addons\FileHost\Models\FileHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileHostMaintenanceBypass
{
    /**
     * Cache statique pour éviter de charger le préfixe 30 fois par page.
     */
    private static ?string $cachedPrefix = null;

    public function handle(Request $request, Closure $next)
    {
        // 1. Récupérer le préfixe (une seule fois par cycle de vie de l'objet ou via cache statique)
        if (self::$cachedPrefix === null) {
            try {
                self::$cachedPrefix = setting('file_host_prefix', 'drive') ?: 'drive';
            } catch (\Throwable $e) {
                self::$cachedPrefix = 'drive';
            }
        }

        // 2. Vérifier si l'URL commence par notre préfixe
        $path = ltrim($request->getPathInfo(), '/');
        if (!str_starts_with($path, self::$cachedPrefix . '/')) {
            return $next($request);
        }

        // 3. Si on est ici, c'est une URL FileHost. 
        // On intercepte et on sert le fichier DIRECTEMENT pour bypasser Laravel Maintenance.
        
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
                return response('Accès refusé.', 403);
            }

            $mimeType    = $file->mime_type ?? 'application/octet-stream';
            $disposition = 'inline';
            if (in_array($mimeType, ['text/html', 'image/svg+xml', 'text/xml', 'application/xml', 'application/xhtml+xml'], true)) {
                $disposition = 'attachment';
                $mimeType    = 'application/octet-stream';
            }

            $safeName = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $file->original_name);
            $safeName = mb_substr(trim($safeName) ?: 'fichier', 0, 255);

            $file->increment('views');

            return response()->file($realPath, [
                'Content-Type'           => $mimeType,
                'Content-Disposition'    => $disposition . '; filename="' . $safeName . '"',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
                'Cache-Control'          => 'private, max-age=3600',
            ]);

        } catch (\Throwable $e) {
            return $next($request);
        }
    }
}
