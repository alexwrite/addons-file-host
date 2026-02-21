<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

use App\Addons\FileHost\Http\Controllers\FileHostPublicController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Lire le préfixe depuis la table de configuration de l'addon
$prefix = 'drive';
try {
    if (Schema::hasTable('file_host_config')) {
        $row = DB::table('file_host_config')->where('key', 'prefix')->first();
        if ($row && !empty($row->value)) {
            $prefix = $row->value;
        }
    }
} catch (\Exception $e) {
    // Silencieux : fallback sur 'drive'
}

Route::get('/' . $prefix . '/{uuid}', [FileHostPublicController::class, 'download'])
    ->name('file-host.download')
    ->where('uuid', '.*')
    ->withoutMiddleware([
        // Ignorer le mode maintenance Laravel (site en maintenance = fichiers toujours accessibles)
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
    ]);


