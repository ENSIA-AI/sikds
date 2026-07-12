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
                <i class="fa-solid fa-globe text-[#1c398e]"></i>
                {{ __('Langue d\'affichage') }}
            </h3>
            <p class="text-xs text-slate-500 mb-4">{{ __('Choisissez la langue de l\'interface.') }}</p>
            <div class="space-y-2">
                @php $currentLocale = app()->getLocale(); @endphp
                @foreach (config('languages.lang', []) as $code => $label)
                    @if ($code === $currentLocale)
                        <div class="rounded-[10px] border-2 border-blue-400 bg-blue-50 px-3 py-2.5 text-sm font-medium text-blue-900 flex items-center gap-2">
                            <i class="fa-solid fa-check text-[#1c398e]"></i>{{ $label }}
                            <span class="ms-auto text-[11px] text-[#1c398e]">{{ __('Actif') }}</span>
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
                <i class="fa-solid fa-shield-halved text-[#1c398e]"></i>
                {{ __('Audit & Conformité') }}
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="section" value="audit">
                @if ($errors->any() && old('section') === 'audit')
                    <div class="rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        {{ __('Veuillez corriger les erreurs ci-dessous.') }}
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Conservation (jours)') }}</label>
                        <input type="number" min="30" max="3650" name="retention_days" value="{{ old('section') === 'audit' ? old('retention_days', $managed['audit']['retention_days']) : $managed['audit']['retention_days'] }}" class="w-full text-sm border rounded-[8px] px-2.5 py-2 focus:outline-none focus:ring-1 transition-colors @error('retention_days') border-red-400 focus:border-red-400 focus:ring-red-300 @else border-black/10 focus:border-blue-400 focus:ring-blue-300 @enderror">
                        @error('retention_days')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Export max (jours)') }}</label>
                        <input type="number" min="1" max="365" name="export_max_days" value="{{ old('section') === 'audit' ? old('export_max_days', $managed['audit']['export_max_days']) : $managed['audit']['export_max_days'] }}" class="w-full text-sm border rounded-[8px] px-2.5 py-2 focus:outline-none focus:ring-1 transition-colors @error('export_max_days') border-red-400 focus:border-red-400 focus:ring-red-300 @else border-black/10 focus:border-blue-400 focus:ring-blue-300 @enderror">
                        @error('export_max_days')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-bell text-[#1c398e]"></i>
                {{ __('Notifications Email') }}
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="section" value="notifications">

                @if ($errors->any() && old('section') === 'notifications')
                    <div class="rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        {{ __('Veuillez corriger les erreurs ci-dessous.') }}
                    </div>
                @endif

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
                    <input type="email" name="support_contact_email" value="{{ old('section') === 'notifications' ? old('support_contact_email', $managed['notifications']['support_contact_email']) : $managed['notifications']['support_contact_email'] }}" class="w-full text-sm border rounded-[8px] px-2.5 py-2 focus:outline-none focus:ring-1 transition-colors @error('support_contact_email') border-red-400 focus:border-red-400 focus:ring-red-300 @else border-black/10 focus:border-blue-400 focus:ring-blue-300 @enderror" placeholder="support@mesrs.dz">
                    @error('support_contact_email')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-[11px] text-slate-500 mt-1">{{ __('Cette adresse apparaît en pied de page de chaque email envoyé par SIKDS.') }}</p>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </article>

        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-water text-[#1c398e]"></i>
                {{ __('Filigrane (Watermark)') }}
            </h3>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="section" value="watermark">
                @if ($errors->any() && old('section') === 'watermark')
                    <div class="rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        @error('visible_fields'){{ $message }}@enderror
                        @error('metadata_fields')<span class="block">{{ $message }}</span>@enderror
                        @if (! $errors->has('visible_fields') && ! $errors->has('metadata_fields')){{ __('Veuillez corriger les erreurs ci-dessous.') }}@endif
                    </div>
                @endif
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

        {{-- ===== RAG / AI ===== --}}
        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-1 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-robot text-[#1c398e]"></i>
                {{ __('Assistant IA (RAG)') }}
            </h3>
            <p class="text-xs text-slate-500 mb-4">{{ __('Réglages de récupération et de génération. Les secrets et points d\'accès restent gérés par l\'environnement.') }}</p>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="section" value="rag">
                @if ($errors->any() && old('section') === 'rag')
                    <div class="rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        {{ __('Veuillez corriger les erreurs ci-dessous.') }}
                    </div>
                @endif
                @php $isRag = old('section') === 'rag'; @endphp
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Modèle de génération') }}</label>
                        <input type="text" name="llm_model" value="{{ $isRag ? old('llm_model', $managed['rag']['llm_model']) : $managed['rag']['llm_model'] }}" class="w-full text-sm border rounded-[8px] px-2.5 py-2 focus:outline-none focus:ring-1 transition-colors @error('llm_model') border-red-400 focus:border-red-400 focus:ring-red-300 @else border-black/10 focus:border-blue-400 focus:ring-blue-300 @enderror" placeholder="llama-3.3-70b-versatile">
                        @error('llm_model')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Seuil de confiance (0–1)') }}</label>
                        <input type="number" step="0.01" min="0" max="1" name="min_confidence" value="{{ $isRag ? old('min_confidence', $managed['rag']['min_confidence']) : $managed['rag']['min_confidence'] }}" class="w-full text-sm border rounded-[8px] px-2.5 py-2 focus:outline-none focus:ring-1 transition-colors @error('min_confidence') border-red-400 focus:border-red-400 focus:ring-red-300 @else border-black/10 focus:border-blue-400 focus:ring-blue-300 @enderror">
                        @error('min_confidence')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                        <p class="text-[11px] text-slate-500 mt-1">{{ __('Extraits sous ce score sont ignorés.') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Bassin de candidats') }}</label>
                        <input type="number" min="1" max="200" name="candidate_pool" value="{{ $isRag ? old('candidate_pool', $managed['rag']['candidate_pool']) : $managed['rag']['candidate_pool'] }}" class="w-full text-sm border rounded-[8px] px-2.5 py-2 focus:outline-none focus:ring-1 transition-colors @error('candidate_pool') border-red-400 focus:border-red-400 focus:ring-red-300 @else border-black/10 focus:border-blue-400 focus:ring-blue-300 @enderror">
                        @error('candidate_pool')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                        <p class="text-[11px] text-slate-500 mt-1">{{ __('Nombre d\'extraits récupérés avant reclassement.') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-slate-700">{{ __('Extraits transmis au modèle (top N)') }}</label>
                        <input type="number" min="1" max="50" name="top_n" value="{{ $isRag ? old('top_n', $managed['rag']['top_n']) : $managed['rag']['top_n'] }}" class="w-full text-sm border rounded-[8px] px-2.5 py-2 focus:outline-none focus:ring-1 transition-colors @error('top_n') border-red-400 focus:border-red-400 focus:ring-red-300 @else border-black/10 focus:border-blue-400 focus:ring-blue-300 @enderror">
                        @error('top_n')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col justify-center gap-2.5">
                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                            <input type="checkbox" name="reranking_enabled" value="1" @checked($managed['rag']['reranking_enabled']) class="w-4 h-4 rounded accent-blue-600">
                            <span class="font-medium text-slate-800">{{ __('Activer le reclassement (reranking)') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                            <input type="checkbox" name="hybrid_enabled" value="1" @checked($managed['rag']['hybrid_enabled']) class="w-4 h-4 rounded accent-blue-600">
                            <span class="font-medium text-slate-800">{{ __('Recherche hybride (sémantique + lexicale)') }}</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white rounded-[8px] transition-all hover:shadow-md" style="background-color:var(--sikds-primary);">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </article>

        {{-- ===== System Health (read-only) ===== --}}
        <article class="bg-white rounded-[14px] border border-black/10 p-5 lg:col-span-2" style="box-shadow:var(--sikds-shadow-panel);">
            <h3 class="text-base font-bold mb-4 flex items-center gap-2" style="color:var(--sikds-ink);">
                <i class="fa-solid fa-heart-pulse text-[#1c398e]"></i>
                {{ __('État du système') }}
            </h3>
            @php
                $bytes = (int) ($health['storage_bytes'] ?? 0);
                $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
                $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
                $i = min($i, count($units) - 1);
                $storageHuman = round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
                $retentionDays = (int) ($managed['audit']['retention_days'] ?? 0);
                $oldestAudit = $health['oldest_audit_at'] ?? null;
                $nextPurge = ($oldestAudit && $retentionDays > 0)
                    ? \Illuminate\Support\Carbon::parse($oldestAudit)->addDays($retentionDays)
                    : null;
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('Base de données') }}</div>
                    <div class="font-semibold flex items-center gap-1.5 {{ ($health['db_ok'] ?? false) ? 'text-green-700' : 'text-red-700' }}">
                        <i class="fa-solid {{ ($health['db_ok'] ?? false) ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                        {{ ($health['db_ok'] ?? false) ? __('Connectée') : __('Indisponible') }}
                    </div>
                </div>
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('File d\'attente') }}</div>
                    <div class="font-semibold text-slate-800">{{ $health['queue_connection'] ?? '—' }}</div>
                </div>
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('Environnement') }}</div>
                    <div class="font-semibold text-slate-800">{{ $health['environment'] ?? '—' }}</div>
                </div>
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('Versions') }}</div>
                    <div class="font-semibold text-slate-800 text-xs">Laravel {{ $health['laravel_version'] ?? '—' }} · PHP {{ $health['php_version'] ?? '—' }}</div>
                </div>
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('Documents') }}</div>
                    <div class="font-semibold text-slate-800">{{ number_format($health['documents_count'] ?? 0) }}</div>
                </div>
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('Utilisateurs') }}</div>
                    <div class="font-semibold text-slate-800">{{ number_format($health['users_count'] ?? 0) }}</div>
                </div>
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('Stockage documents') }}</div>
                    <div class="font-semibold text-slate-800">{{ $storageHuman }}</div>
                </div>
                <div class="rounded-[10px] border border-black/5 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-500">{{ __('Journaux d\'audit') }}</div>
                    <div class="font-semibold text-slate-800">{{ number_format($health['audit_logs_count'] ?? 0) }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        {{ $nextPurge ? __('Purge prévue : :date', ['date' => $nextPurge->format('Y-m-d')]) : __('Aucune purge planifiée') }}
                    </div>
                </div>
            </div>
        </article>
    </div>
@endsection
