<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

namespace App\Addons\FileHost;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Helper statique pour gérer le bypass de maintenance.
 * Utilisé par le ServiceProvider ET le contrôleur (pour les mises à jour de préfixe).
 */
class FileHostBypassHelper
{
    /**
     * Flag partagé entre tous les addons pour n'enregistrer la carte
     * "corentin-website" qu'une seule fois par requête.
     */
    public static bool $cardRegistered = false;

    /**
     * Lit le préfixe depuis la table de configuration.
     */
    public static function readPrefix(): string
    {
        try {
            if (Schema::hasTable('file_host_config')) {
                $row = DB::table('file_host_config')->where('key', 'prefix')->first();
                if ($row && !empty($row->value)) {
                    return trim($row->value, '/');
                }
            }
        } catch (\Throwable $e) {}

        return 'drive';
    }

    /**
     * Copie le script de service dans /public et met à jour le .htaccess
     * avec la règle correspondant au préfixe actuel.
     *
     * @param string|null $prefix  Préfixe à utiliser (lit en BDD si null)
     */
    public static function install(?string $prefix = null): void
    {
        try {
            if ($prefix === null) {
                $prefix = self::readPrefix();
            }
            $prefix = trim($prefix, '/');

            $publicPath   = public_path();
            $servScript   = __DIR__ . '/../public/file-host-serve.php';
            $destScript   = $publicPath . '/file-host-serve.php';
            $htaccessPath = $publicPath . '/.htaccess';

            // 1. Copier le script de service dans /public
            if (file_exists($servScript)) {
                if (!file_exists($destScript) || md5_file($servScript) !== md5_file($destScript)) {
                    if (!@copy($servScript, $destScript)) {
                        Log::warning('FileHost: impossible de copier file-host-serve.php vers ' . $destScript);
                    }
                }
            } else {
                Log::warning('FileHost: script source introuvable : ' . $servScript);
            }

            // 2. Mettre à jour le .htaccess
            if (!file_exists($htaccessPath)) {
                Log::warning('FileHost: .htaccess introuvable à ' . $htaccessPath);
                return;
            }

            if (!is_writable($htaccessPath)) {
                Log::warning('FileHost: .htaccess non modifiable à ' . $htaccessPath);
                return;
            }

            $marker    = '# FileHost-Bypass-BEGIN';
            $endMarker = '# FileHost-Bypass-END';
            $content   = file_get_contents($htaccessPath);

            // Supprimer l'ancien bloc FileHost s'il existe
            $content = preg_replace(
                '/' . preg_quote($marker, '/') . '.*?' . preg_quote($endMarker, '/') . '\r?\n?/s',
                '',
                $content
            );

            // Construire la nouvelle règle Apache
            // Le pattern matche /<prefix>/ et tout ce qui suit (UUID de n'importe quelle forme)
            $escapedPrefix = preg_quote($prefix, '/');
            $rule = "\n{$marker}\n"
                . "<IfModule mod_rewrite.c>\n"
                . "RewriteCond %{REQUEST_URI} ^/{$escapedPrefix}(/.*)?$\n"
                . "RewriteRule .* /file-host-serve.php [QSA,L]\n"
                . "</IfModule>\n"
                . "{$endMarker}\n";

            // Insérer juste après "RewriteEngine On"
            if (str_contains($content, 'RewriteEngine On') || str_contains($content, 'RewriteEngine on')) {
                $content = preg_replace(
                    '/(RewriteEngine\s+[Oo]n\s*[\r\n]+)/u',
                    "$1{$rule}",
                    $content,
                    1
                );
            } else {
                // Pas de RewriteEngine On trouvé → ajouter en tête
                $content = "<IfModule mod_rewrite.c>\nRewriteEngine On\n</IfModule>\n{$rule}\n" . $content;
            }

            file_put_contents($htaccessPath, $content);
            Log::info("FileHost: .htaccess mis à jour pour le préfixe '/{$prefix}/'.");

        } catch (\Throwable $e) {
            Log::error('FileHost: erreur lors de l\'installation du bypass : ' . $e->getMessage());
        }
    }

    /**
     * Supprime la règle FileHost du .htaccess (désinstallation propre).
     */
    public static function uninstall(): void
    {
        try {
            $htaccessPath = public_path('.htaccess');
            if (!file_exists($htaccessPath) || !is_writable($htaccessPath)) {
                return;
            }

            $marker    = '# FileHost-Bypass-BEGIN';
            $endMarker = '# FileHost-Bypass-END';
            $content   = file_get_contents($htaccessPath);

            $content = preg_replace(
                '/' . preg_quote($marker, '/') . '.*?' . preg_quote($endMarker, '/') . '\r?\n?/s',
                '',
                $content
            );

            file_put_contents($htaccessPath, $content);
        } catch (\Throwable $e) {}
    }
}
