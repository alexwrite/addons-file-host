<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

namespace App\Addons\FileHost\Http\Controllers;

use App\Addons\FileHost\Models\FileHost;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FileHostPublicController extends Controller
{
    /**
     * Cette méthode sert de point d'entrée pour les routes. 
     * Note: Le middleware FileHostMaintenanceBypass s'occupe normalement
     * d'intercepter la requête pour bypasser le mode maintenance.
     */
    public function download($uuid)
    {
        $uuid = str_replace("\0", '', $uuid);
        if (str_contains($uuid, '..')) {
            abort(400);
        }

        $file = FileHost::where('uuid', $uuid)->firstOrFail();

        if (!Storage::exists($file->file_path)) {
            abort(404);
        }

        $storagePath = realpath(storage_path('app'));
        $filePath    = Storage::path($file->file_path);
        $realPath    = realpath($filePath);

        if ($realPath === false || !str_starts_with($realPath, $storagePath . DIRECTORY_SEPARATOR)) {
            abort(403);
        }

        $file->increment('views');

        $mimeType = $file->mime_type ?? 'application/octet-stream';
        $disposition = 'inline';
        if (in_array($mimeType, ['text/html', 'image/svg+xml', 'text/xml', 'application/xml', 'application/xhtml+xml'], true)) {
            $disposition = 'attachment';
            $mimeType    = 'application/octet-stream';
        }

        $safeName = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $file->original_name);
        $safeName = mb_substr(trim($safeName) ?: 'fichier', 0, 255);

        return response()->file($realPath, [
            'Content-Type'           => $mimeType,
            'Content-Disposition'    => $disposition . '; filename="' . $safeName . '"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'Cache-Control'          => 'private, max-age=3600',
        ]);
    }
}
