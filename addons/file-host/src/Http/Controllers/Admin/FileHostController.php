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

namespace App\Addons\FileHost\Http\Controllers\Admin;

use App\Addons\FileHost\Models\FileHost;
use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileHostController extends Controller
{
    public function index()
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        
        $files = FileHost::orderBy('created_at', 'desc')->paginate(15);
        $prefix = setting('file_host_prefix', 'drive');

        return view('file-host_admin::index', [
            'files'  => $files,
            'prefix' => $prefix,
        ]);
    }

    public function updateSettings(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        $request->validate([
            'file_host_prefix' => 'required|string|max:100',
        ]);

        $prefix = trim($request->file_host_prefix, '/');
        $prefix = preg_replace('/[^a-zA-Z0-9\-\_\/]/', '', $prefix);
        $prefix = preg_replace('/\.\.+/', '', $prefix);
        $prefix = preg_replace('/\/+/', '/', $prefix);
        $prefix = trim($prefix, '/');
        $prefix = $prefix ?: 'drive';

        if (class_exists(\App\Models\Admin\Setting::class)) {
            \App\Models\Admin\Setting::updateSettings(['file_host_prefix' => $prefix]);
        } else {
            setting(['file_host_prefix' => $prefix]);
        }

        return redirect()->route('admin.file-host.index')->with('success', __('file-host::messages.success_update'));
    }

    public function upload(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        $request->validate([
            'file' => 'required|file|max:51200',
        ]);

        if (!$request->hasFile('file')) {
            return redirect()->route('admin.file-host.index');
        }

        $file = $request->file('file');
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (in_array($ext, FileHost::BLOCKED_EXTENSIONS, true)) {
            return redirect()->route('admin.file-host.index')
                ->with('error', __('file-host::messages.error_extension', ['ext' => $ext]));
        }

        $realMime = $file->getMimeType();
        $this->checkDangerousMime($realMime, $ext);

        $originalName = $this->sanitizeFileName($file->getClientOriginalName());
        $uuid = (string) Str::uuid() . '.' . $ext;
        $filePath = $file->storeAs('public/host-drive', $uuid);

        if (!$filePath) {
            return redirect()->route('admin.file-host.index');
        }

        FileHost::create([
            'uuid'          => $uuid,
            'original_name' => $originalName,
            'file_path'     => $filePath,
            'mime_type'     => $realMime,
            'file_size'     => $file->getSize(),
            'admin_id'      => auth()->guard('admin')->id(),
        ]);

        return redirect()->route('admin.file-host.index')->with('success', __('file-host::messages.success_upload', ['name' => $originalName]));
    }

    public function update(Request $request, $id)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

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
            $ext = strtolower(pathinfo($newUuid, PATHINFO_EXTENSION));
            if (in_array($ext, FileHost::BLOCKED_EXTENSIONS, true)) {
                return redirect()->route('admin.file-host.index')
                    ->with('error', __('file-host::messages.error_extension', ['ext' => $ext]));
            }

            $file->uuid = $newUuid;
            $file->save();
        }

        return redirect()->route('admin.file-host.index')->with('success', __('file-host::messages.success_update'));
    }

    public function destroy($id)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        $file = FileHost::findOrFail($id);
        if (Storage::exists($file->file_path)) {
            Storage::delete($file->file_path);
        }
        $file->delete();
        return redirect()->route('admin.file-host.index')->with('success', __('file-host::messages.success_delete'));
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
            throw new \RuntimeException(__('file-host::messages.forbidden_type'));
        }
    }
}
