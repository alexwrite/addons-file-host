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

use App\Addons\FileHost\Http\Controllers\FileHostPublicController;
use Illuminate\Support\Facades\Route;

$prefix = setting('file_host_prefix', 'drive');

Route::get('/' . $prefix . '/{uuid}', [FileHostPublicController::class, 'download'])
    ->name('file-host.download')
    ->where('uuid', '[a-zA-Z0-9\.\-_]+(?:\/[a-zA-Z0-9\.\-_]+)*')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
    ]);
