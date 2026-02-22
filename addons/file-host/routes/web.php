<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

use App\Addons\FileHost\Http\Controllers\FileHostPublicController;
use Illuminate\Support\Facades\Route;

// Lire le préfixe depuis le service de configuration standard de ClientXCMS
$prefix = setting('file_host_prefix', 'drive');
if (empty($prefix)) {
    $prefix = 'drive';
}

Route::get('/' . $prefix . '/{uuid}', [FileHostPublicController::class, 'download'])

    ->name('file-host.download')
    ->where('uuid', '[a-zA-Z0-9\/\.\-_]+') // Restriction pour éviter les caractères spéciaux dangereux
    ->withoutMiddleware([
        // Permet l'accès même si le mode maintenance est activé via Blade/Laravel
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
    ]);
