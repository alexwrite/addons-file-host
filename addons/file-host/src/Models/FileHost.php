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
