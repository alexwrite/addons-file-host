@extends('admin.layouts.admin')

@section('title', 'Hébergement de Fichiers')

@section('content')
<div class="container mx-auto">
    @include('admin/shared/alerts')

    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">

                {{-- ===== GRAND RECTANGLE PRINCIPAL ===== --}}
                <div class="card">

                    {{-- En-tête du card --}}
                    <div class="card-heading">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                <i class="bi bi-hdd mr-2"></i>Hébergement de Fichiers
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Gérez vos fichiers hébergés et obtenez des liens directs.
                            </p>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                            {{ $files->total() ?? 0 }} fichier(s)
                        </div>
                    </div>

                    {{-- Alerts --}}
                    @if(session('success'))
                        <div class="mx-4 mt-4 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 text-sm font-medium">
                            <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mx-4 mt-4 flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 text-sm font-medium">
                            <i class="bi bi-exclamation-triangle-fill"></i>{{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="mx-4 mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 text-sm">
                            <ul class="list-disc pl-4 space-y-1">
                                @foreach($errors->all() as $error)<li class="font-medium">{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ===== DEUX RECTANGLES : UPLOAD + CONFIG (dans le grand) ===== --}}
                    <div class="p-4 border-b dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {{-- Upload --}}
                            <div class="border rounded-lg dark:border-gray-700 overflow-hidden">
                                <div class="px-4 py-3 border-b dark:border-gray-700 bg-gray-50 dark:bg-slate-800 flex items-center gap-2">
                                    <i class="bi bi-cloud-arrow-up text-gray-600 dark:text-gray-400"></i>
                                    <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">Uploader un fichier</span>
                                </div>
                                <div class="p-4">
                                    <form action="{{ route('admin.file-host.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                                        @csrf
                                        <input type="file" name="file" id="fileInput" style="display:none;" onchange="onFileChosen(this)" required>

                                        <div id="dropZone"
                                             onclick="document.getElementById('fileInput').click()"
                                             ondragover="event.preventDefault();this.classList.add('dz-over')"
                                             ondragleave="this.classList.remove('dz-over')"
                                             ondrop="handleFileDrop(event)"
                                             class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer transition-all hover:border-gray-400 dark:hover:border-gray-400 hover:bg-gray-50 dark:hover:bg-slate-800">

                                            <div id="dz-icon" class="text-4xl text-gray-300 dark:text-gray-600 mb-2 transition-transform">
                                                <i class="bi bi-cloud-arrow-up-fill"></i>
                                            </div>
                                            <div id="dz-title" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Glisser un fichier ici</div>
                                            <div id="dz-sub" class="text-xs text-gray-400 mt-1">
                                                ou <span class="text-gray-700 dark:text-gray-300 underline font-semibold">cliquer pour choisir</span> — 50 Mo maximum
                                            </div>
                                            <div id="dz-file-info" style="display:none" class="mt-3">
                                                <div class="inline-flex items-center gap-2 bg-gray-100 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5">
                                                    <i class="bi bi-file-earmark-check text-green-500"></i>
                                                    <span id="dz-filename" class="text-xs font-bold text-gray-700 dark:text-gray-300 max-w-[180px] truncate block"></span>
                                                    <span id="dz-filesize" class="text-xs text-gray-400"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="dz-actions" style="display:none" class="mt-3 flex justify-end items-center gap-2">
                                            <button type="button" onclick="resetUpload()" class="btn btn-light text-sm">Annuler</button>
                                            <button type="submit" class="btn btn-primary text-sm">
                                                <i class="bi bi-upload mr-1"></i> Envoyer le fichier
                                            </button>
                                        </div>
                                        <div id="dz-footer" class="mt-2 text-right">
                                            <span class="text-xs text-gray-300 dark:text-gray-600">
                                                <i class="bi bi-lock mr-1"></i>Hébergé en privé sur votre serveur.
                                            </span>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Configuration --}}
                            <div class="border rounded-lg dark:border-gray-700 overflow-hidden">
                                <div class="px-4 py-3 border-b dark:border-gray-700 bg-gray-50 dark:bg-slate-800 flex items-center gap-2">
                                    <i class="bi bi-gear text-gray-600 dark:text-gray-400"></i>
                                    <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">Configuration de l'URL</span>
                                </div>
                                <div class="p-4">
                                    {{-- Avertissement --}}
                                    <div class="flex items-start gap-2 mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-700/50">
                                        <i class="bi bi-exclamation-circle-fill text-amber-500 mt-0.5 flex-shrink-0"></i>
                                        <div>
                                            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Changement de l'adresse des liens</p>
                                            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5 leading-relaxed">
                                                Après avoir cliqué sur "Enregistrer", <strong>actualisez la page</strong> pour que les nouveaux liens soient bien pris en compte.
                                                Les anciens liens ne fonctionneront plus.
                                            </p>
                                        </div>
                                    </div>
                                    <form action="{{ route('admin.file-host.settings.update') }}" method="POST">
                                        @csrf
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Préfixe de l'URL</label>
                                        <div class="flex rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden mb-3">
                                            <span class="flex items-center px-3 bg-gray-50 dark:bg-slate-800 text-gray-400 text-sm border-r border-gray-200 dark:border-gray-600 whitespace-nowrap">
                                                {{ rtrim(config('app.url'), '/') }}/
                                            </span>
                                            <input type="text" name="file_host_prefix" value="{{ $prefix ?? 'drive' }}"
                                                   class="input-text rounded-none border-0 flex-1 text-sm" placeholder="drive" required>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-400"><i class="bi bi-link-45deg"></i> /{{ $prefix ?? 'drive' }}/fichier.png</span>
                                            <button type="submit" class="btn btn-primary text-sm">
                                                <i class="bi bi-check-lg mr-1"></i> Enregistrer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== TABLEAU DES FICHIERS (dans le grand rectangle) ===== --}}
                    <div class="border rounded-lg overflow-hidden dark:border-gray-700 m-4">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">Fichier</span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">Lien</span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">Taille</span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">Vues</span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">Date</span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($files as $file)
                                @php
                                    $isPreviewable = str_starts_with($file->mime_type, 'image/')
                                        || $file->mime_type === 'application/pdf'
                                        || str_starts_with($file->mime_type, 'video/');
                                    $previewType = str_starts_with($file->mime_type, 'image/') ? 'image'
                                        : ($file->mime_type === 'application/pdf' ? 'pdf'
                                        : (str_starts_with($file->mime_type, 'video/') ? 'video' : ''));
                                @endphp
                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                    {{-- Fichier --}}
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 flex items-center justify-center bg-gray-50 dark:bg-slate-800 text-xl flex-shrink-0 {{ $isPreviewable ? 'cursor-pointer' : '' }}"
                                                 @if($isPreviewable) onclick="openPreview('{{ $file->url }}', '{{ addslashes($file->original_name) }}', '{{ $previewType }}')" title="Voir l'aperçu" @endif>
                                                @if(str_starts_with($file->mime_type, 'image/'))
                                                    <img src="{{ $file->url }}" alt="{{ $file->original_name }}" class="w-full h-full object-cover">
                                                @elseif($file->mime_type === 'application/pdf')
                                                    <i class="bi bi-file-earmark-pdf text-red-500"></i>
                                                @elseif(str_starts_with($file->mime_type, 'video/'))
                                                    <i class="bi bi-file-earmark-play text-orange-400"></i>
                                                @elseif(str_starts_with($file->mime_type, 'audio/'))
                                                    <i class="bi bi-file-earmark-music text-gray-400"></i>
                                                @elseif(str_contains($file->mime_type, 'zip') || str_contains($file->mime_type, 'rar'))
                                                    <i class="bi bi-file-earmark-zip text-gray-400"></i>
                                                @else
                                                    <i class="bi bi-file-earmark text-gray-400"></i>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                @if($isPreviewable)
                                                    <div class="flex items-center gap-1 cursor-pointer group"
                                                         onclick="openPreview('{{ $file->url }}', '{{ addslashes($file->original_name) }}', '{{ $previewType }}')"
                                                         title="Cliquer pour aperçu">
                                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate max-w-[180px] group-hover:underline">{{ $file->original_name }}</span>
                                                        <i class="bi bi-eye text-gray-400 text-xs flex-shrink-0"></i>
                                                    </div>
                                                @else
                                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate max-w-[200px] block">{{ $file->original_name }}</span>
                                                @endif
                                                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                                    {{ explode('/', $file->mime_type ?? '')[1] ?? '—' }}
                                                    @if($isPreviewable)<span class="normal-case text-gray-300 dark:text-gray-600">· aperçu disponible</span>@endif
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Lien --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="text-xs bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 px-2 py-1 rounded block max-w-[160px] truncate" title="{{ $file->url }}">
                                            /{{ $prefix ?? 'drive' }}/{{ \Illuminate\Support\Str::limit($file->uuid, 20) }}
                                        </code>
                                    </td>

                                    {{-- Taille --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php $kb = $file->file_size / 1024; @endphp
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $kb >= 1024 ? number_format($kb/1024, 2).' Mo' : number_format($kb, 0).' Ko' }}
                                        </span>
                                    </td>

                                    {{-- Vues --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            <i class="bi bi-eye mr-1"></i>{{ $file->views }}
                                        </span>
                                    </td>

                                    {{-- Date --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $file->created_at ? $file->created_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            @if($isPreviewable)
                                            <button type="button" title="Aperçu"
                                                onclick="openPreview('{{ $file->url }}', '{{ addslashes($file->original_name) }}', '{{ $previewType }}')"
                                                class="btn-icon"><i class="bi bi-eye"></i></button>
                                            @endif
                                            <button type="button" title="Modifier"
                                                onclick="openEditModal({{ $file->id }}, '{{ htmlspecialchars(addslashes($file->original_name)) }}', '{{ htmlspecialchars(addslashes($file->uuid)) }}')"
                                                class="btn-icon"><i class="bi bi-pencil"></i></button>
                                            <button type="button" title="Copier le lien"
                                                onclick="copyLink('{{ $file->url }}')"
                                                class="btn-icon"><i class="bi bi-clipboard"></i></button>
                                            <a href="{{ $file->url }}" target="_blank" title="Ouvrir" class="btn-icon">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                            <form action="{{ route('admin.file-host.destroy', $file->id) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Supprimer ce fichier définitivement ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Supprimer" class="btn-icon text-red-500 hover:text-red-700">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="bg-white dark:bg-slate-900">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="bi bi-cloud-upload text-4xl text-gray-300 dark:text-gray-600 mb-2"></i>
                                            <p class="text-gray-500 dark:text-gray-400">Aucun fichier hébergé pour le moment.</p>
                                            <p class="text-xs text-gray-400 mt-1">Utilisez le formulaire ci-dessus pour envoyer votre premier fichier.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if(method_exists($files, 'links') && $files->hasPages())
                    <div class="py-1 px-4 mx-auto">
                        {!! $files->appends(request()->query())->links('pagination::tailwind') !!}
                    </div>
                    @endif

                </div>{{-- /card --}}
            </div>
        </div>
    </div>
</div>

{{-- Copy toast --}}
<div id="copy-toast" style="position:fixed;bottom:1.5rem;right:1.5rem;background:#1e293b;color:#f8fafc;padding:.75rem 1.25rem;border-radius:.875rem;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:.5rem;box-shadow:0 10px 30px rgba(0,0,0,.25);transform:translateY(5rem);opacity:0;transition:all .3s;z-index:9999;">
    <i class="bi bi-check-circle-fill" style="color:#22c55e;"></i> Lien copié !
</div>

{{-- Preview plein écran --}}
<div id="previewBackdrop" onclick="if(event.target===this)closePreview()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:2000;align-items:center;justify-content:center;flex-direction:column;padding:1rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;max-width:64rem;margin-bottom:1rem;">
        <div>
            <div id="preview-name" style="font-size:1rem;font-weight:800;color:#f8fafc;"></div>
            <div id="preview-meta" style="font-size:.75rem;color:#64748b;margin-top:.15rem;"></div>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <a id="preview-open-link" href="#" target="_blank"
               style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.1);color:#e2e8f0;border:1px solid rgba(255,255,255,.15);border-radius:.75rem;padding:.4rem .875rem;font-size:.8rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-box-arrow-up-right"></i> Ouvrir
            </a>
            <button onclick="closePreview()"
                    style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:#f8fafc;width:2.25rem;height:2.25rem;border-radius:.75rem;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div id="preview-content"
         style="width:100%;max-width:64rem;max-height:85vh;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:1rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);">
    </div>
</div>

{{-- Edit Modal --}}
<div id="editBackdrop" onclick="if(event.target===this)closeEditModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:#fff;border-radius:1.5rem;max-width:30rem;width:95%;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.2);" class="dark:bg-slate-900">
        <div style="padding:1.25rem 1.5rem;background:#1e293b;display:flex;align-items:center;gap:.75rem;">
            <i class="bi bi-pencil-square text-white text-lg"></i>
            <span style="font-weight:800;color:#fff;font-size:1rem;">Modifier le fichier</span>
        </div>
        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div style="padding:1.25rem;display:flex;flex-direction:column;gap:.875rem;">
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.375rem;">Nom du fichier</label>
                    <input type="text" name="original_name" id="edit_original_name" class="input-text" required>
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.375rem;">Lien URL</label>
                    <div style="display:flex;border:1.5px solid #e2e8f0;border-radius:.875rem;overflow:hidden;">
                        <span style="display:flex;align-items:center;padding:.625rem .75rem;background:#f1f5f9;color:#94a3b8;font-size:.8rem;white-space:nowrap;font-weight:700;border-right:1.5px solid #e2e8f0;">
                            /{{ $prefix ?? 'drive' }}/
                        </span>
                        <input type="text" name="uuid" id="edit_uuid"
                               style="flex:1;padding:.625rem .875rem;border:none;outline:none;font-size:.875rem;background:#fff;color:#0f172a;min-width:0;" required>
                    </div>
                    <p style="margin-top:.4rem;font-size:.72rem;color:#94a3b8;">Exemple : <code style="background:#f1f5f9;color:#475569;border-radius:.3rem;padding:.1rem .3rem;">images/logo.png</code></p>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:.875rem 1.25rem;background:#f8fafc;border-top:1px solid #f1f5f9;" class="dark:bg-slate-800/50 dark:border-slate-800">
                <button type="button" onclick="closeEditModal()" class="btn btn-light">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg mr-1"></i> Sauvegarder
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        const t = document.getElementById('copy-toast');
        t.style.transform = 'translateY(0)'; t.style.opacity = '1';
        setTimeout(() => { t.style.transform = 'translateY(5rem)'; t.style.opacity = '0'; }, 2500);
    });
}
function openPreview(url, name, type) {
    document.getElementById('preview-name').textContent = name;
    document.getElementById('preview-meta').textContent = type.toUpperCase();
    document.getElementById('preview-open-link').href = url;
    const c = document.getElementById('preview-content');
    c.innerHTML = ''; c.style.height = '';
    if (type === 'image') {
        c.innerHTML = `<img src="${url}" style="max-width:100%;max-height:85vh;object-fit:contain;border-radius:.75rem;display:block;" alt="${name}">`;
    } else if (type === 'pdf') {
        c.style.height = '85vh';
        c.innerHTML = `<iframe src="${url}" style="width:100%;height:100%;border:none;border-radius:.75rem;"></iframe>`;
    } else if (type === 'video') {
        c.innerHTML = `<video controls autoplay style="max-width:100%;max-height:85vh;border-radius:.75rem;"><source src="${url}">Non supporté.</video>`;
    }
    document.getElementById('previewBackdrop').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closePreview() {
    document.getElementById('previewBackdrop').style.display = 'none';
    document.getElementById('preview-content').innerHTML = '';
    document.body.style.overflow = '';
}
function openEditModal(id, name, uuid) {
    document.getElementById('editForm').action = `{{ url(admin_prefix() . '/file-host') }}/${id}`;
    document.getElementById('edit_original_name').value = name;
    document.getElementById('edit_uuid').value = uuid;
    document.getElementById('editBackdrop').style.display = 'flex';
}
function closeEditModal() { document.getElementById('editBackdrop').style.display = 'none'; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closePreview(); closeEditModal(); } });

/* DROPZONE */
function onFileChosen(i) { if (i.files && i.files[0]) showFileInfo(i.files[0]); }
function handleFileDrop(e) {
    e.preventDefault();
    document.getElementById('dropZone').classList.remove('dz-over');
    const f = e.dataTransfer.files;
    if (f && f[0]) { const i = document.getElementById('fileInput'); const d = new DataTransfer(); d.items.add(f[0]); i.files = d.files; showFileInfo(f[0]); }
}
function showFileInfo(f) {
    document.getElementById('dz-filename').textContent = f.name;
    const kb = f.size / 1024;
    document.getElementById('dz-filesize').textContent = kb >= 1024 ? '(' + (kb/1024).toFixed(2) + ' Mo)' : '(' + Math.round(kb) + ' Ko)';
    document.getElementById('dz-file-info').style.display = 'block';
    document.getElementById('dz-actions').style.display = 'flex';
    document.getElementById('dz-footer').style.display = 'none';
    document.getElementById('dz-title').textContent = 'Fichier sélectionné ✓';
    document.getElementById('dz-sub').textContent = 'Cliquez sur "Envoyer le fichier" pour uploader.';
    document.getElementById('dz-icon').innerHTML = '<i class="bi bi-check-circle-fill" style="color:#22c55e;font-size:2.5rem;"></i>';
}
function resetUpload() {
    document.getElementById('fileInput').value = '';
    document.getElementById('dz-file-info').style.display = 'none';
    document.getElementById('dz-actions').style.display = 'none';
    document.getElementById('dz-footer').style.display = 'block';
    document.getElementById('dz-title').textContent = 'Glisser un fichier ici';
    document.getElementById('dz-sub').innerHTML = 'ou <span class="text-gray-700 dark:text-gray-300 underline font-semibold">cliquer pour choisir</span> — 50 Mo maximum';
    document.getElementById('dz-icon').innerHTML = '<i class="bi bi-cloud-arrow-up-fill"></i>';
}
</script>
@endsection
