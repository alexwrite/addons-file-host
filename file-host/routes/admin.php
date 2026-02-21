<?php

use App\Addons\FileHost\Http\Controllers\Admin\FileHostController;
use Illuminate\Support\Facades\Route;

Route::prefix('file-host')->name('file-host.')->group(function () {
    Route::get('/', [FileHostController::class, 'index'])->name('index');
    Route::post('/settings', [FileHostController::class, 'updateSettings'])->name('settings.update');
    Route::post('/upload', [FileHostController::class, 'upload'])->name('upload');
    Route::put('/{id}', [FileHostController::class, 'update'])->name('update');
    Route::delete('/{id}', [FileHostController::class, 'destroy'])->name('destroy');
});
