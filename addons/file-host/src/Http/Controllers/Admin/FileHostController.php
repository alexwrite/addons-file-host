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

            $prefix = trim($request->file_host_prefix, '/');
            $prefix = preg_replace('/[^a-zA-Z0-9\-\_\/]/', '', $prefix);
            $prefix = preg_replace('/\.\.+/', '', $prefix);
            $prefix = preg_replace('/\/+/', '/', $prefix);
            $prefix = trim($prefix, '/');
            $prefix = $prefix ?: 'drive';

            // Sauvegarder via le service de configuration officiel du CMS
            if (class_exists(\App\Models\Admin\Setting::class)) {
                \App\Models\Admin\Setting::updateSettings(['file_host_prefix' => $prefix]);
            } else {
                // Fallback au cas où
                setting(['file_host_prefix' => $prefix]);
            }


            return redirect()->route('admin.file-host.index')->with('success', "Paramètres mis à jour : /{$prefix}/");

        } catch (\Throwable $e) {
            Log::error('FileHost Settings Update Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }

    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:51200',
            ]);

            if (!$request->hasFile('file')) {
                return redirect()->route('admin.file-host.index')->with('error', 'Aucun fichier reçu.');
            }

            $file = $request->file('file');
            $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
                return redirect()->route('admin.file-host.index')
                    ->with('error', "L'extension «.{$ext}» n'est pas autorisée.");
            }

            $realMime = $file->getMimeType();
            $this->checkDangerousMime($realMime, $ext);

            $originalName = $this->sanitizeFileName($file->getClientOriginalName());
            $uuid = (string) Str::uuid() . '.' . $ext;
            $filePath = $file->storeAs('public/host-drive', $uuid);

            if (!$filePath) {
                return redirect()->route('admin.file-host.index')->with('error', 'Échec du stockage.');
            }

            FileHost::create([
                'uuid'          => $uuid,
                'original_name' => $originalName,
                'file_path'     => $filePath,
                'mime_type'     => $realMime,
                'file_size'     => $file->getSize(),
                'admin_id'      => auth()->guard('admin')->id(),
            ]);

            return redirect()->route('admin.file-host.index')->with('success', "Fichier « {$originalName} » hébergé !");

        } catch (\Throwable $e) {
            Log::error('FileHost Upload Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de l\'envoi.');
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
            $file->original_name = $this->sanitizeFileName($request->original_name);

            $newUuid = strtolower($request->uuid);
            $newUuid = preg_replace('/[^a-z0-9\/\.\-_]/', '-', $newUuid);
            $newUuid = preg_replace('/\.\.+/', '', $newUuid);
            $newUuid = trim($newUuid, '/');

            if (!empty($newUuid)) {
                $file->uuid = $newUuid;
                $file->save();
            }

            return redirect()->route('admin.file-host.index')->with('success', 'Fichier mis à jour.');

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour.');
        }
    }

    public function destroy($id)
    {
        try {
            $file = FileHost::findOrFail($id);
            if (Storage::exists($file->file_path)) {
                Storage::delete($file->file_path);
            }
            $file->delete();
            return redirect()->route('admin.file-host.index')->with('success', 'Supprimé.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    private function sanitizeFileName(string $name): string
    {
        $name = str_replace("\0", '', $name);
        $name = str_replace(['../', '..\\', '/', '\\'], '', $name);
        $name = preg_replace('/[^\w\s\.\-\(\)\[\]àâäéèêëîïôùûüç]/u', '', $name);
        return mb_substr(trim($name) ?: 'fichier', 0, 200);
    }

    private function checkDangerousMime(string $mime, string $ext): void
    {
        $dangerous = ['application/x-php', 'application/php', 'text/x-php'];
        if (in_array($mime, $dangerous, true)) {
            throw new \RuntimeException("Type interdit.");
        }
    }
}
