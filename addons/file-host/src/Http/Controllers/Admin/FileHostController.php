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
use Illuminate\Support\Facades\Log;

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

    public function index()
    {
        try {
            $files = FileHost::orderBy('created_at', 'desc')->paginate(15);
            $prefix = setting('file_host_prefix', 'drive');

            return view('file-host::admin.index', [
                'files'  => $files,
                'prefix' => $prefix,
            ]);

        } catch (\Throwable $e) {
            Log::error('FileHost Index Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors du chargement de la liste.');
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

            // Sauvegarder via le service de configuration de ClientXCMS
            app('settings')->update(['file_host_prefix' => $prefix]);

            return redirect()->route('admin.file-host.index')->with('success', "Paramètres mis à jour : /{$prefix}/");

        } catch (\Throwable $e) {
            Log::error('FileHost Settings Update Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour des paramètres.');
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
            Log::error('FileHost Upload Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de l\'envoi du fichier.');
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
            Log::error('FileHost Update Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour.');
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
            Log::error('FileHost Delete Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    private function sanitizeFileName(string $name): string
    {
        $name = str_replace("\0", '', $name);
        $name = str_replace(['../', '..\\', '/', '\\'], '', $name);
        $name = preg_replace('/[^\w\s\.\-\(\)\[\]àâäéèêëîïôùûüç]/u', '', $name);
        $name = trim($name);
        return mb_substr($name ?: 'fichier', 0, 200);
    }

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

        if ($mime === 'text/plain' && in_array($ext, ['php', 'sh', 'pl', 'py', 'rb'], true)) {
            throw new \RuntimeException("Fichier texte avec extension exécutable interdite (.{$ext}).");
        }
    }
}
