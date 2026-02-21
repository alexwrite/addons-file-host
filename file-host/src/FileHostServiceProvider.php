<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

namespace App\Addons\FileHost;

use App\Extensions\BaseAddonServiceProvider;
use App\Models\Admin\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class FileHostServiceProvider extends BaseAddonServiceProvider
{
    protected string $uuid = 'file-host';

    public function register()
    {
        $this->app->singleton('file-host', function ($app) {
            return $this;
        });
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
        $this->initializeSettings();
        $this->autoInstallIfNeeded();
        $this->bypassMaintenanceMode();
    }

    /**
     * Installe / met à jour le bypass de maintenance via le helper dédié.
     */
    protected function bypassMaintenanceMode(): void
    {
        \App\Addons\FileHost\FileHostBypassHelper::install();
    }

    protected function initializeSettings(): void
    {
        try {
            if (Schema::hasTable('file_host_config')) {
                DB::table('file_host_config')->insertOrIgnore([
                    'key'        => 'prefix',
                    'value'      => 'drive',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {}
    }

    protected function registerSettingsItems(): void
    {
        try {
            $settings = $this->app->make('settings');

            // Créer la carte parente UNE SEULE FOIS par requête.
            // Utilise $GLOBALS pour être indépendant de tout addon tiers.
            // N'importe quel futur addon peut copier ce même pattern sans rien modifier.
            if (empty($GLOBALS['corentin_website_card_registered'])) {
                $settings->addCard(
                    'corentin-website',
                    'Corentin WebSite Addons',
                    'Gérer les extensions et automatisations Corentin WebSite.',
                    55
                );
                $GLOBALS['corentin_website_card_registered'] = true;
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

    protected function autoInstallIfNeeded(): void
    {
        try {
            // 1. Créer la table principale si elle n'existe pas
            if (!Schema::hasTable('file_hosts')) {
                Schema::create('file_hosts', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('uuid', 255)->unique();
                    $table->string('original_name');
                    $table->string('file_path');
                    $table->string('mime_type')->nullable();
                    $table->unsignedBigInteger('file_size')->default(0);
                    $table->unsignedBigInteger('admin_id')->nullable();
                    $table->unsignedBigInteger('views')->default(0);
                    $table->timestamps();
                });
            } else {
                // 2. Corriger la colonne uuid si elle est encore trop courte (36 chars)
                try {
                    $colInfo = DB::select("SHOW COLUMNS FROM `file_hosts` LIKE 'uuid'");
                    if (!empty($colInfo)) {
                        $colType = strtolower($colInfo[0]->Type ?? '');
                        if (str_contains($colType, '36') || $colType === 'char(36)') {
                            DB::statement('ALTER TABLE `file_hosts` MODIFY `uuid` VARCHAR(255) NOT NULL');
                            Log::info('FileHost: colonne uuid agrandie à VARCHAR(255).');
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('FileHost: impossible de vérifier la colonne uuid: ' . $e->getMessage());
                }
            }

            // 3. Créer la table de configuration si elle n'existe pas
            if (!Schema::hasTable('file_host_config')) {
                Schema::create('file_host_config', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('key')->primary();
                    $table->text('value')->nullable();
                    $table->timestamps();
                });
            }

            // 4. S'assurer que la valeur par défaut du préfixe existe
            DB::table('file_host_config')->insertOrIgnore([
                'key'        => 'prefix',
                'value'      => 'drive',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('FileHost: Erreur auto-install: ' . $e->getMessage());
        }
    }
}
