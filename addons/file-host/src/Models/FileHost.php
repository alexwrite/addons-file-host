<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

namespace App\Addons\FileHost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FileHost extends Model
{
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
}
