@extends('layouts.app')
@section('page_title', 'Paramètres')
@section('page_subtitle', 'Configuration et personnalisation de SIKDS')
@section('content')
    <div class="mb-6 flex items-center justify-end gap-3">
        <a href="{{ route('settings.index') }}" class="px-4 py-2 text-sm font-medium rounded-[10px] border border-black/10 hover:bg-gray-50 transition-colors flex items-center gap-2">
            <i class="fa-solid fa-rotate-right"></i>
            Rafraîchir
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[12px] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-[12px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-center gap-2">
            <i class="fa-solid fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <article class="bg-white rounded-[14px] border border-black/10 p-5" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-globe text-blue-600"></i>
                Langue d'affichage
            </h3>
            <p class="text-xs text-slate-500 mb-4">Langue actuelle de l'interface.</p>
            <div class="space-y-2">
                <div class="rounded-[10px] border-2 border-blue-400 bg-blue-50 px-3 py-2.5 text-sm font-medium text-blue-900 flex items-center gap-2">
                    <i class="fa-solid fa-check text-blue-600"></i>Français
                </div>
                <div class="rounded-[10px] border border-black/10 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-500 flex items-center gap-2 opacity-60">
                    <i class="fa-solid fa-globe text-slate-400"></i>العربية
                    <span class="ml-auto text-[11px] text-slate-500">À venir</span>
                </div>
                <div class="rounded-[10px] border border-black/10 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-500 flex items-center gap-2 opacity-60">
                    <i class="fa-solid fa-globe text-slate-400"></i>English
                    <span class="ml-auto text-[11px] text-slate-500">À venir</span>
                </div>
            </div>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-shield-halved text-amber-600"></i>
                Audit & Conformité
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="section" value="audit">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">Conservation (jours)</label>
                        <input type="number" min="30" max="3650" name="retention_days" value="{{ $managed['audit']['retention_days'] }}" class="w-full text-sm border border-black/10 rounded-[8px] px-2.5 py-2 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-300 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">Export max (jours)</label>
                        <input type="number" min="1" max="365" name="export_max_days" value="{{ $managed['audit']['export_max_days'] }}" class="w-full text-sm border border-black/10 rounded-[8px] px-2.5 py-2 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-300 transition-colors">
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">Enregistrer</button>
                </div>
            </form>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-bell text-purple-600"></i>
                Notifications Email
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="section" value="notifications">

                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-black/5">
                    <label class="flex items-start gap-2.5 text-xs cursor-pointer">
                        <input type="checkbox" name="document_published_enabled" value="1" @checked($managed['notifications']['document_published_enabled']) class="w-4 h-4 rounded accent-blue-600 mt-0.5">
                        <span>
                            <div class="font-medium text-slate-800">Publication</div>
                            <div class="text-slate-500 text-xs">Notifier lors d'une nouvelle publication (Brouillon → Actif)</div>
                        </span>
                    </label>
                    <label class="flex items-start gap-2.5 text-xs cursor-pointer">
                        <input type="checkbox" name="document_updated_enabled" value="1" @checked($managed['notifications']['document_updated_enabled']) class="w-4 h-4 rounded accent-blue-600 mt-0.5">
                        <span>
                            <div class="font-medium text-slate-800">Mise à jour</div>
                            <div class="text-slate-500 text-xs">Notifier lors d'une nouvelle version</div>
                        </span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5 text-slate-700">Adresse de support (affichée dans les emails)</label>
                    <input type="email" name="support_contact_email" value="{{ $managed['notifications']['support_contact_email'] }}" class="w-full text-sm border border-black/10 rounded-[8px] px-2.5 py-2 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-300 transition-colors" placeholder="support@mesrs.dz">
                    <p class="text-[11px] text-slate-500 mt-1">Cette adresse apparaît en pied de page de chaque email envoyé par SIKDS.</p>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">Enregistrer</button>
                </div>
            </form>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-water text-teal-600"></i>
                Filigrane (Watermark)
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="section" value="watermark">
                @php
                    $visibleValues = collect($managed['watermark']['visible_fields'] ?? [])->map(fn ($v) => (string) $v)->all();
                    $metadataValues = collect($managed['watermark']['metadata_fields'] ?? [])->map(fn ($v) => (string) $v)->all();
                @endphp
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold mb-2 text-slate-700">Champs visibles (overlay sur PDF)</label>
                        <div class="space-y-1.5 text-xs">
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="full_name" @checked(in_array('full_name', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> Nom complet</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="institution" @checked(in_array('institution', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> Institution</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="timestamp" @checked(in_array('timestamp', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> Date/heure</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="uuid" @checked(in_array('uuid', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> ID unique</label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-2 text-slate-700">Métadonnées XMP (intégrées au PDF)</label>
                        <div class="space-y-1.5 text-xs">
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="full_name" @checked(in_array('full_name', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> Nom complet</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="institution" @checked(in_array('institution', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> Institution</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="email" @checked(in_array('email', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> Email</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="timestamp" @checked(in_array('timestamp', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> Date/heure</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="uuid" @checked(in_array('uuid', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> ID unique</label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">Enregistrer</button>
                </div>
            </form>
        </article>
    </div>
@endsection
