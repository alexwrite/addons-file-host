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

namespace App\Addons\FileHost\Http\Middleware;

use App\Addons\FileHost\Models\FileHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileHostMaintenanceBypass
{
    private static ?string $cachedPrefix = null;

    public function handle(Request $request, Closure $next)
    {
        if (self::$cachedPrefix === null) {
            try {
                self::$cachedPrefix = setting('file_host_prefix', 'drive') ?: 'drive';
            } catch (\Throwable $e) {
                self::$cachedPrefix = 'drive';
            }
        }

        $path = ltrim($request->getPathInfo(), '/');
        if (!str_starts_with($path, self::$cachedPrefix . '/')) {
            return $next($request);
        }

        $uuid = substr($path, strlen(self::$cachedPrefix) + 1);
        
        try {
            $response = FileHost::serve($uuid);

            if ($response !== null) {
                return $response;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FileHost bypass error: ' . $e->getMessage());
        }

        return $next($request);
    }
}
