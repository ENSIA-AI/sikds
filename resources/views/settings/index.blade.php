@extends('layouts.app')
@section('page_title', __('Paramètres'))
@section('page_subtitle', __('Configuration et personnalisation de SIKDS'))
@section('content')
    <div class="mb-6 flex items-center justify-end gap-3">
        <a href="{{ route('settings.index') }}" class="px-4 py-2 text-sm font-medium rounded-[10px] border border-black/10 hover:bg-gray-50 transition-colors flex items-center gap-2">
            <i class="fa-solid fa-rotate-right"></i>
            {{ __('Rafraîchir') }}
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
                <i class="fa-solid fa-globe text-[#1e3a8a]"></i>
                {{ __('Langue d\'affichage') }}
            </h3>
            <p class="text-xs text-slate-500 mb-4">{{ __('Choisissez la langue de l\'interface.') }}</p>
            <div class="space-y-2">
                @php $currentLocale = app()->getLocale(); @endphp
                @foreach (config('languages.lang', []) as $code => $label)
                    @if ($code === $currentLocale)
                        <div class="rounded-[10px] border-2 border-blue-400 bg-blue-50 px-3 py-2.5 text-sm font-medium text-blue-900 flex items-center gap-2">
                            <i class="fa-solid fa-check text-blue-600"></i>{{ $label }}
                            <span class="ms-auto text-[11px] text-blue-600">{{ __('Actif') }}</span>
                        </div>
                    @else
                        <a href="{{ route('changeLanguage', ['lang' => $code]) }}"
                           class="rounded-[10px] border border-black/10 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 flex items-center gap-2 hover:bg-slate-50 hover:border-blue-300 transition-colors">
                            <i class="fa-solid fa-globe text-slate-400"></i>{{ $label }}
                        </a>
                    @endif
                @endforeach
            </div>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-shield-halved text-[#1e3a8a]"></i>
                {{ __('Audit & Conformité') }}
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="section" value="audit">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Conservation (jours)') }}</label>
                        <input type="number" min="30" max="3650" name="retention_days" value="{{ $managed['audit']['retention_days'] }}" class="w-full text-sm border border-black/10 rounded-[8px] px-2.5 py-2 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-300 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Export max (jours)') }}</label>
                        <input type="number" min="1" max="365" name="export_max_days" value="{{ $managed['audit']['export_max_days'] }}" class="w-full text-sm border border-black/10 rounded-[8px] px-2.5 py-2 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-300 transition-colors">
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-bell text-[#1e3a8a]"></i>
                {{ __('Notifications Email') }}
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="section" value="notifications">

                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-black/5">
                    <label class="flex items-start gap-2.5 text-xs cursor-pointer">
                        <input type="checkbox" name="document_published_enabled" value="1" @checked($managed['notifications']['document_published_enabled']) class="w-4 h-4 rounded accent-blue-600 mt-0.5">
                        <span>
                            <div class="font-medium text-slate-800">{{ __('Publication') }}</div>
                            <div class="text-slate-500 text-xs">{{ __('Notifier lors d\'une nouvelle publication (Brouillon → Actif)') }}</div>
                        </span>
                    </label>
                    <label class="flex items-start gap-2.5 text-xs cursor-pointer">
                        <input type="checkbox" name="document_updated_enabled" value="1" @checked($managed['notifications']['document_updated_enabled']) class="w-4 h-4 rounded accent-blue-600 mt-0.5">
                        <span>
                            <div class="font-medium text-slate-800">{{ __('Mise à jour') }}</div>
                            <div class="text-slate-500 text-xs">{{ __('Notifier lors d\'une nouvelle version') }}</div>
                        </span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Adresse de support (affichée dans les emails)') }}</label>
                    <input type="email" name="support_contact_email" value="{{ $managed['notifications']['support_contact_email'] }}" class="w-full text-sm border border-black/10 rounded-[8px] px-2.5 py-2 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-300 transition-colors" placeholder="support@mesrs.dz">
                    <p class="text-[11px] text-slate-500 mt-1">{{ __('Cette adresse apparaît en pied de page de chaque email envoyé par SIKDS.') }}</p>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-water text-[#1e3a8a]"></i>
                {{ __('Filigrane (Watermark)') }}
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
                        <label class="block text-xs font-semibold mb-2 text-slate-700">{{ __('Champs visibles (overlay sur PDF)') }}</label>
                        <div class="space-y-1.5 text-xs">
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="full_name" @checked(in_array('full_name', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('Nom complet') }}</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="institution" @checked(in_array('institution', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('Institution') }}</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="timestamp" @checked(in_array('timestamp', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('Date/heure') }}</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="visible_fields[]" value="uuid" @checked(in_array('uuid', $visibleValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('ID unique') }}</label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-2 text-slate-700">{{ __('Métadonnées XMP (intégrées au PDF)') }}</label>
                        <div class="space-y-1.5 text-xs">
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="full_name" @checked(in_array('full_name', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('Nom complet') }}</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="institution" @checked(in_array('institution', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('Institution') }}</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="email" @checked(in_array('email', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('Email') }}</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="timestamp" @checked(in_array('timestamp', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('Date/heure') }}</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="metadata_fields[]" value="uuid" @checked(in_array('uuid', $metadataValues, true)) class="w-3.5 h-3.5 rounded accent-blue-600"> {{ __('ID unique') }}</label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </article>
    </div>
@endsection
