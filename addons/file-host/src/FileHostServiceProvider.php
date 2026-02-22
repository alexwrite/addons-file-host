<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

namespace App\Addons\FileHost;

use App\Addons\FileHost\Http\Middleware\FileHostMaintenanceBypass;
use App\Extensions\BaseAddonServiceProvider;
use App\Models\Admin\Permission;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class FileHostServiceProvider extends BaseAddonServiceProvider
{
    protected string $uuid = 'file-host';

    public function register()
    {
        $this->app->singleton('file-host', function ($app) {
            return $this;
        });

        // Enregistrer le middleware GLOBALEMENT en haut de la pile.
        // C'est l'unique moyen de bypasser le mode maintenance de Laravel sans toucher au .htaccess.
        if ($this->app->bound(\Illuminate\Contracts\Http\Kernel::class) && !app()->bound('file_host_middleware_registered')) {
            $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
                ->prependMiddleware(FileHostMaintenanceBypass::class);
            
            app()->instance('file_host_middleware_registered', true);
        }
    }

    public function boot()
    {
        $viewsPath      = __DIR__ . '/../views';
        $langPath       = __DIR__ . '/../lang';
        $migrationsPath = __DIR__ . '/../database/migrations';
        $adminRoutesPath = __DIR__ . '/../routes/admin.php';
        $webRoutesPath  = __DIR__ . '/../routes/web.php';

        $this->loadViewsFrom($viewsPath, 'file-host');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'file-host');
        }

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        if (File::exists($adminRoutesPath)) {
            Route::middleware(['web', 'admin'])
                ->prefix(admin_prefix())
                ->name('admin.')
                ->group(function () use ($adminRoutesPath) {
                    require $adminRoutesPath;
                });
        }

        if (File::exists($webRoutesPath)) {
            Route::middleware(['web'])
                ->group(function () use ($webRoutesPath) {
                    require $webRoutesPath;
                });
        }

        $this->registerSettingsItems();
    }

    protected function registerSettingsItems(): void
    {
        try {
            $settings = $this->app->make('settings');

            if (!app()->bound('corentin_website_card_registered')) {
                $settings->addCard(
                    'corentin-website',
                    'Corentin WebSite Addons',
                    'Gérer les extensions et automatisations Corentin WebSite.',
                    55
                );
                app()->instance('corentin_website_card_registered', true);
            }

            $settings->addCardItem(
                'corentin-website',
                'file-host',
                'Hébergement de Fichiers',
                'Gérer vos fichiers hébergés (Images, PDF...)',
                'bi bi-folder2-open',
                [\App\Addons\FileHost\Http\Controllers\Admin\FileHostController::class, 'index'],
                Permission::MANAGE_EXTENSIONS
            );

        } catch (\Exception $e) {
            Log::error("FileHost: Erreur menu: {$e->getMessage()}");
        }
    }
}
