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
    public function download($uuid)
    {
        // ── Sécurité 1 : sanitiser l'UUID reçu depuis l'URL ──────────────────
        // Supprimer null bytes et bloquer path traversal
        $uuid = str_replace("\0", '', $uuid);

        if (str_contains($uuid, '..') || str_contains($uuid, "\0")) {
            abort(400, 'Requête invalide.');
        }

        // ── Récupérer le fichier depuis la base ───────────────────────────────
        // L'UUID vient de l'URL mais le file_path vient de la BDD — pas d'injection possible
        $file = FileHost::where('uuid', $uuid)->firstOrFail();

        // ── Sécurité 2 : vérifier que le fichier existe dans le stockage ──────
        if (!Storage::exists($file->file_path)) {
            abort(404, 'Fichier introuvable sur le serveur.');
        }

        // ── Sécurité 3 : path traversal — vérifier que le chemin reste dans /storage/app ─
        $storagePath = realpath(storage_path('app'));
        $filePath    = Storage::path($file->file_path);
        $realPath    = realpath($filePath);

        if ($realPath === false || $storagePath === false) {
            abort(404, 'Fichier inaccessible.');
        }

        if (!str_starts_with($realPath, $storagePath . DIRECTORY_SEPARATOR)) {
            abort(403, 'Accès refusé.');
        }

        // ── Incrémenter les vues ──────────────────────────────────────────────
        $file->increment('views');

        // ── Sanitiser le nom de fichier pour le header Content-Disposition ────
        $safeName = $this->sanitizeHeaderValue($file->original_name);

        // ── Sécurité 4 : headers de sécurité ─────────────────────────────────
        // On utilise le MIME type stocké en base (vérifié au moment de l'upload)
        // Pour les fichiers HTML/SVG, on force le téléchargement pour éviter XSS
        $mimeType = $file->mime_type ?? 'application/octet-stream';
        $disposition = 'inline';

        if (in_array($mimeType, ['text/html', 'image/svg+xml', 'text/xml', 'application/xml'], true)) {
            $disposition = 'attachment'; // forcer le téléchargement, pas l'affichage
            $mimeType    = 'application/octet-stream';
        }

        return response()->file($realPath, [
            'Content-Type'              => $mimeType,
            'Content-Disposition'       => $disposition . '; filename="' . $safeName . '"',
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'Referrer-Policy'           => 'no-referrer',
            'Cache-Control'             => 'private, max-age=3600',
        ]);
    }

    /**
     * Supprime les caractères de contrôle d'une valeur de header HTTP
     * pour éviter l'injection de headers (CRLF injection).
     */
    private function sanitizeHeaderValue(string $value): string
    {
        // Supprimer null bytes, CR et LF (injection de headers CRLF)
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        $value = str_replace(['"', '\\'], ['\'', ''], $value);
        return mb_substr(trim($value) ?: 'fichier', 0, 255);
    }
}
