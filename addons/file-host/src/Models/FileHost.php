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

namespace App\Addons\FileHost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileHost extends Model
{
    public const DOWNLOAD_ONLY_MIMES = [
        'text/html',
        'image/svg+xml',
        'text/xml',
        'application/xml',
        'application/xhtml+xml'
    ];

    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'asp', 'aspx', 'cfm', 'cfc', 'jsp', 'jspx',
        'pl', 'py', 'rb', 'sh', 'bash', 'zsh', 'fish', 'ksh',
        'bat', 'cmd', 'ps1', 'vbs', 'wsf',
        'exe', 'com', 'msi', 'dll', 'so',
        'htaccess', 'htpasswd', 'ini', 'env',
        'cgi', 'shtml', 'xhtml',
    ];

    /**
     * Logique centralisée pour servir un fichier avec contrôles de sécurité.
     * Évite la duplication entre Middleware et Contrôleurs.
     * 
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public static function serve(string $uuid)
    {
        $uuid = str_replace("\0", '', $uuid);
        if (empty($uuid) || str_contains($uuid, '..')) {
            return null;
        }

        $file = self::where('uuid', $uuid)->first();
        if (!$file) {
            return null;
        }

        // Sécurité supplémentaire : Vérifier l'extension
        $ext = strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION));
        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            \Illuminate\Support\Facades\Log::warning("FileHost: Tentative d'accès à une extension interdite ($ext) pour UUID {$uuid}");
            return response(__('file-host::messages.access_denied'), 403);
        }

        if (!Storage::exists($file->file_path)) {
            \Illuminate\Support\Facades\Log::error("FileHost: Fichier manquant sur le disque pour UUID {$uuid}: {$file->file_path}");
            return null;
        }

        $storagePath = realpath(storage_path('app'));
        $filePath    = Storage::path($file->file_path);
        $realPath    = realpath($filePath);

        // Sécurité : Empêcher le path traversal si le fichier est hors du storage
        if ($realPath === false || !str_starts_with($realPath, $storagePath . DIRECTORY_SEPARATOR)) {
            return response(__('file-host::messages.access_denied'), 403);
        }

        $file->increment('views');

        $mimeType = $file->mime_type ?? 'application/octet-stream';
        $disposition = 'inline';
        if (in_array($mimeType, self::DOWNLOAD_ONLY_MIMES, true)) {
            $disposition = 'attachment';
            $mimeType    = 'application/octet-stream';
        }

        $safeName = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $file->original_name);
        $safeName = mb_substr(trim($safeName) ?: 'fichier', 0, 255);

        return response()->file($realPath, [
            'Content-Type'           => $mimeType,
            'Content-Disposition'    => $disposition . '; filename="' . addslashes($safeName) . '"; filename*=UTF-8\'\'' . rawurlencode($safeName),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Robots-Tag'           => 'noindex, nofollow',
            'Cache-Control'          => 'private, max-age=3600',
        ]);
    }

    /**
     * Récupère le préfixe de l'URL avec fallback.
     */
    public static function getPrefix(): string
    {
        try {
            return setting('file_host_prefix', 'drive') ?: 'drive';
        } catch (\Throwable) {
            return 'drive';
        }
    }

    protected $table = 'file_hosts';

    protected $fillable = [
        'uuid',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'admin_id',
        'views'
    ];

    protected $appends = ['url', 'human_size'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getUrlAttribute()
    {
        return route('file-host.download', $this->uuid);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $maxIndex = count($units) - 1;

        for ($i = 0; $bytes > 1024 && $i < $maxIndex; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
