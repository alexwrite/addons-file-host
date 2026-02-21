<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

namespace App\Addons\FileHost\Http\Controllers\Admin;

use App\Addons\FileHost\Models\FileHost;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHostController extends Controller
{
    // ─── Extensions interdites (exécutables côté serveur) ────────────────────
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'asp', 'aspx', 'cfm', 'cfc', 'jsp', 'jspx',
        'pl', 'py', 'rb', 'sh', 'bash', 'zsh', 'fish', 'ksh',
        'bat', 'cmd', 'ps1', 'vbs', 'wsf',
        'exe', 'com', 'msi', 'dll', 'so',
        'htaccess', 'htpasswd', 'ini', 'env',
        'cgi', 'shtml', 'xhtml',
    ];

    /**
     * Affiche une page de débogage lisible en cas d'erreur fatale.
     * IMPORTANT : uniquement accessible aux admins (route middlewarisée).
     */
    private function debugPage(\Throwable $e, string $action = ''): \Illuminate\Http\Response
    {
        $title  = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file   = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line   = (int) $e->getLine();
        $trace  = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        $action = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>FileHost — Erreur de débogage</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; padding: 2rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 1rem; padding: 2rem; max-width: 960px; margin: 0 auto; }
        .badge { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .8rem; border-radius: 9999px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 1.5rem; }
        .badge-error { background: rgba(244,63,94,.15); color: #f43f5e; border: 1px solid rgba(244,63,94,.3); }
        h1 { font-size: 1.4rem; font-weight: 800; color: #f8fafc; line-height: 1.4; margin-bottom: 1.5rem; word-break: break-word; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .meta-item { background: #0f172a; border: 1px solid #334155; border-radius: .75rem; padding: 1rem; }
        .meta-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: .35rem; }
        .meta-value { font-size: .875rem; color: #94a3b8; word-break: break-all; }
        .meta-value code { color: #f59e0b; font-family: 'Consolas', monospace; }
        .trace-title { font-size: .75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: .75rem; }
        pre { background: #0f172a; border: 1px solid #334155; border-radius: .75rem; padding: 1.25rem; overflow-x: auto; font-size: .72rem; line-height: 1.7; color: #94a3b8; font-family: 'Consolas', monospace; white-space: pre-wrap; word-break: break-all; max-height: 350px; overflow-y: auto; }
        .back { display: inline-flex; align-items: center; gap: .5rem; margin-bottom: 1.5rem; background: #334155; color: #e2e8f0; border: none; border-radius: .875rem; padding: .5rem 1rem; font-size: .875rem; font-weight: 600; cursor: pointer; text-decoration: none; }
        .back:hover { background: #475569; }
        hr { border: none; border-top: 1px solid #334155; margin: 1.5rem 0; }
    </style>
</head>
<body>
    <div class="card">
        <a class="back" href="javascript:history.back()">← Retour</a>
        <div class="badge badge-error">⚠ FileHost — Erreur fatale{$action}</div>
        <h1>{$title}</h1>
        <div class="meta">
            <div class="meta-item">
                <div class="meta-label">Fichier source</div>
                <div class="meta-value"><code>{$file}</code></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Ligne</div>
                <div class="meta-value"><code>{$line}</code></div>
            </div>
        </div>
        <hr>
        <div class="trace-title">Stack trace complet</div>
        <pre>{$trace}</pre>
    </div>
</body>
</html>
HTML;

        return response($html, 500)->header('Content-Type', 'text/html');
    }

    public function index()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('file_hosts')) {
                $files = FileHost::orderBy('created_at', 'desc')->paginate(15);
            } else {
                $files = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
                    'path' => request()->url(),
                ]);
            }

            $prefix = 'drive';
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('file_host_config')) {
                    $row = \Illuminate\Support\Facades\DB::table('file_host_config')->where('key', 'prefix')->first();
                    if ($row) {
                        $prefix = $row->value;
                    }
                }
            } catch (\Exception $e) {}

            return view('file-host::admin.index', [
                'files'  => $files,
                'prefix' => $prefix,
            ]);

        } catch (\Throwable $e) {
            return $this->debugPage($e, ' · index()');
        }
    }

    public function updateSettings(Request $request)
    {
        try {
            $request->validate([
                'file_host_prefix' => 'required|string|max:100',
            ]);

            // Sanitiser : lettres, chiffres, tirets, underscores, slashs uniquement
            $prefix = trim($request->file_host_prefix, '/');
            $prefix = preg_replace('/[^a-zA-Z0-9\-\_\/]/', '', $prefix);
            // Bloquer les path traversal
            $prefix = preg_replace('/\.\.+/', '', $prefix);
            $prefix = preg_replace('/\/+/', '/', $prefix);
            $prefix = trim($prefix, '/');
            $prefix = $prefix ?: 'drive';

            if (!$this->ensureConfigTable()) {
                return redirect()->route('admin.file-host.index')->with('error', 'Erreur lors de la mise à jour du préfixe.');
            }

            \Illuminate\Support\Facades\DB::table('file_host_config')->where('key', 'prefix')->delete();
            \Illuminate\Support\Facades\DB::table('file_host_config')->insert([
                'key'        => 'prefix',
                'value'      => $prefix,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mettre à jour le .htaccess immédiatement avec le nouveau préfixe
            \App\Addons\FileHost\FileHostBypassHelper::install($prefix);

            return redirect()->route('admin.file-host.index')->with('success', "Préfixe mis à jour : /{$prefix}/ — Actualisez la page pour voir les nouveaux liens.");

        } catch (\Throwable $e) {
            return $this->debugPage($e, ' · updateSettings()');
        }
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:51200', // 50 Mo max
            ]);

            if (!$request->hasFile('file')) {
                return redirect()->route('admin.file-host.index')->with('error', 'Aucun fichier reçu.');
            }

            $file = $request->file('file');

            // ── Sécurité 1 : bloquer les extensions dangereuses ───────────────
            $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
                return redirect()->route('admin.file-host.index')
                    ->with('error', "L'extension «.{$ext}» n'est pas autorisée pour des raisons de sécurité.");
            }

            // ── Sécurité 2 : MIME type réel (via fileinfo, pas le navigateur) ─
            $realMime = $file->getMimeType(); // fileinfo, non falsifiable
            $this->checkDangerousMime($realMime, $ext);

            // ── Sécurité 3 : sanitiser le nom original ────────────────────────
            $originalName = $this->sanitizeFileName($file->getClientOriginalName());

            // ── Générer un UUID propre ─────────────────────────────────────────
            $uuid = (string) Str::uuid() . '.' . $ext;

            // ── Stocker le fichier ────────────────────────────────────────────
            $filePath = $file->storeAs('public/host-drive', $uuid);

            if (!$filePath) {
                return redirect()->route('admin.file-host.index')->with('error', 'Échec du stockage du fichier.');
            }

            // ── Vérifier que le chemin final est bien dans le dossier attendu ─
            $storageDisk = storage_path('app/public/host-drive');
            $finalPath   = realpath(storage_path('app/' . $filePath));
            if ($finalPath === false || !str_starts_with($finalPath, realpath($storageDisk))) {
                Storage::delete($filePath);
                return redirect()->route('admin.file-host.index')->with('error', 'Chemin de fichier suspect détecté.');
            }

            FileHost::create([
                'uuid'          => $uuid,
                'original_name' => $originalName,
                'file_path'     => $filePath,
                'mime_type'     => $realMime,
                'file_size'     => $file->getSize(),
                'admin_id'      => auth()->guard('admin')->id(),
            ]);

            return redirect()->route('admin.file-host.index')->with('success', "Fichier « {$originalName} » hébergé avec succès !");

        } catch (\Throwable $e) {
            return $this->debugPage($e, ' · upload()');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'original_name' => 'required|string|max:255',
                'uuid'          => 'required|string|max:255|unique:file_hosts,uuid,' . $id,
            ]);

            $file = FileHost::findOrFail($id);

            // ── Sanitiser le nom ───────────────────────────────────────────────
            $file->original_name = $this->sanitizeFileName($request->original_name);

            // ── Sanitiser le nouvel UUID / chemin public ───────────────────────
            $newUuid = strtolower($request->uuid);
            $newUuid = preg_replace('/[^a-z0-9\/\.\-_]/', '-', $newUuid); // caractères autorisés
            $newUuid = preg_replace('/\.\.+/', '', $newUuid);              // bloquer path traversal
            $newUuid = preg_replace('/\/+/', '/', $newUuid);               // normaliser les slashs
            $newUuid = trim($newUuid, '/');

            if (empty($newUuid)) {
                return redirect()->route('admin.file-host.index')->with('error', 'UUID invalide.');
            }

            $file->uuid = $newUuid;
            $file->save();

            return redirect()->route('admin.file-host.index')->with('success', 'Fichier mis à jour avec succès.');

        } catch (\Throwable $e) {
            return $this->debugPage($e, ' · update(id=' . (int)$id . ')');
        }
    }

    public function destroy($id)
    {
        try {
            $file = FileHost::findOrFail($id);

            // Supprimer uniquement si le chemin est dans le bon dossier
            if (Storage::exists($file->file_path)) {
                $storageDisk = realpath(storage_path('app/public/host-drive'));
                $realPath    = realpath(Storage::path($file->file_path));

                if ($realPath && $storageDisk && str_starts_with($realPath, $storageDisk)) {
                    Storage::delete($file->file_path);
                }
            }

            $file->delete();

            return redirect()->route('admin.file-host.index')->with('success', 'Fichier supprimé avec succès.');

        } catch (\Throwable $e) {
            return $this->debugPage($e, ' · destroy(id=' . (int)$id . ')');
        }
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Sanitise un nom de fichier : supprime les caractères dangereux,
     * les path traversal, les null bytes et coupe à 200 caractères.
     */
    private function sanitizeFileName(string $name): string
    {
        // Supprimer les null bytes
        $name = str_replace("\0", '', $name);
        // Supprimer les path traversal
        $name = str_replace(['../', '..\\', '/', '\\'], '', $name);
        // Ne garder que les caractères sûrs
        $name = preg_replace('/[^\w\s\.\-\(\)\[\]àâäéèêëîïôùûüç]/u', '', $name);
        $name = trim($name);
        // Limiter la longueur
        return mb_substr($name ?: 'fichier', 0, 200);
    }

    /**
     * Vérifie que le MIME type réel n'est pas dangereux.
     * Lance une exception si le fichier est suspect.
     */
    private function checkDangerousMime(string $mime, string $ext): void
    {
        $dangerousMimes = [
            'application/x-php', 'application/php', 'text/x-php',
            'application/x-httpd-php', 'application/x-sh', 'text/x-sh',
            'application/x-perl', 'application/x-python',
            'application/x-executable', 'application/x-msdos-program',
        ];

        if (in_array($mime, $dangerousMimes, true)) {
            throw new \RuntimeException("Type de fichier interdit détecté ({$mime}).");
        }

        // Double-vérification : MIME "text/plain" pour des extensions exécutables
        if ($mime === 'text/plain' && in_array($ext, ['php', 'sh', 'pl', 'py', 'rb'], true)) {
            throw new \RuntimeException("Fichier texte avec extension exécutable interdite (.{$ext}).");
        }
    }

    /**
     * S'assure que la table de configuration existe.
     */
    private function ensureConfigTable(): bool
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('file_host_config')) {
                \Illuminate\Support\Facades\Schema::create('file_host_config', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('key')->primary();
                    $table->text('value')->nullable();
                    $table->timestamps();
                });
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
