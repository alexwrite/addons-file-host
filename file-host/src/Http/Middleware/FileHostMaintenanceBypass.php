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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Ce middleware s'exécute EN PREMIER dans le pipeline HTTP (prepend).
 * Si l'URL correspond à une URL de fichier hébergé ET que le site est
 * en mode maintenance, il sert le fichier DIRECTEMENT sans appeler
 * $next() — ce qui empêche tout middleware de maintenance de bloquer.
 */
class FileHostMaintenanceBypass
{
    public function handle(Request $request, Closure $next)
    {
        // Passer immédiatement si le site n'est pas en maintenance
        if (!app()->isDownForMaintenance()) {
            return $next($request);
        }

        // ── Lire le préfixe configuré ─────────────────────────────────────────
        $prefix = 'drive';
        try {
            if (Schema::hasTable('file_host_config')) {
                $row = DB::table('file_host_config')->where('key', 'prefix')->first();
                if ($row && !empty($row->value)) {
                    $prefix = trim($row->value, '/');
                }
            }
        } catch (\Throwable $e) {
            // fallback sur 'drive'
        }

        // ── Vérifier si l'URL correspond au préfixe du FileHost ──────────────
        $path        = ltrim($request->getPathInfo(), '/');
        $prefixClean = ltrim($prefix, '/');

        if (!str_starts_with($path, $prefixClean . '/')) {
            return $next($request); // Pas notre URL → maintenance normale
        }

        // ── Extraire l'UUID ───────────────────────────────────────────────────
        $uuid = substr($path, strlen($prefixClean) + 1);

        // Sécurité : bloquer path traversal et null bytes
        $uuid = str_replace("\0", '', $uuid);
        if (empty($uuid) || str_contains($uuid, '..')) {
            return response('Requête invalide.', 400);
        }

        // ── Servir le fichier directement (sans pipeline maintenance) ─────────
        try {
            $file = FileHost::where('uuid', $uuid)->first();

            if (!$file || !Storage::exists($file->file_path)) {
                return response('Fichier non trouvé.', 404);
            }

            // Path traversal check
            $storagePath = realpath(storage_path('app'));
            $filePath    = Storage::path($file->file_path);
            $realPath    = realpath($filePath);

            if ($realPath === false || !str_starts_with($realPath, $storagePath . DIRECTORY_SEPARATOR)) {
                return response('Accès refusé.', 403);
            }

            // MIME forcé pour les types dangereux
            $mimeType    = $file->mime_type ?? 'application/octet-stream';
            $disposition = 'inline';
            if (in_array($mimeType, ['text/html', 'image/svg+xml', 'text/xml'], true)) {
                $disposition = 'attachment';
                $mimeType    = 'application/octet-stream';
            }

            // Sanitiser le nom pour le header
            $safeName = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $file->original_name);
            $safeName = mb_substr(trim($safeName) ?: 'fichier', 0, 255);

            $file->increment('views');

            return response()->file($realPath, [
                'Content-Type'           => $mimeType,
                'Content-Disposition'    => $disposition . '; filename="' . $safeName . '"',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
                'Referrer-Policy'        => 'no-referrer',
                'Cache-Control'          => 'private, max-age=3600',
            ]);

        } catch (\Throwable $e) {
            return response('Erreur lors du chargement du fichier.', 500);
        }
    }
}
