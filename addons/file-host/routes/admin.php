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

use App\Addons\FileHost\Http\Controllers\Admin\FileHostController;
use Illuminate\Support\Facades\Route;

Route::prefix('file-host')->name('file-host.')->group(function () {
    Route::get('/', [FileHostController::class, 'index'])->name('index');
    Route::post('/settings', [FileHostController::class, 'updateSettings'])->name('settings.update');
    Route::post('/upload', [FileHostController::class, 'upload'])->name('upload');
    Route::put('/{id}', [FileHostController::class, 'update'])->name('update');
    Route::delete('/{id}', [FileHostController::class, 'destroy'])->name('destroy');
});
