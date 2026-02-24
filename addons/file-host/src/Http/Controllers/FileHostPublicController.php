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

namespace App\Addons\FileHost\Http\Controllers;

use App\Addons\FileHost\Models\FileHost;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FileHostPublicController extends Controller
{
    public function download($uuid)
    {
        $response = FileHost::serve($uuid);

        if ($response === null) {
            abort(404);
        }

        return $response;
    }
}
