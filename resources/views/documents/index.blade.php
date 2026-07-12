@extends('layouts.app')
@section('page_title', __('Gestion des Documents'))
@section('page_subtitle', __('Gérer le cycle de vie des documents institutionnels'))

@push('scripts')
    @vite(['resources/js/pages/documents.js'])
@endpush

@section('content')

    <div
        x-data="documentListPage({
            csrfToken: @js(csrf_token()),
        })"
        @keydown.escape.window="handleEscape()"
        class="relative"
    >

    <div x-show="banner.message"
         x-cloak
         class="sikds-toast"
         :class="{
            'sikds-toast--success': banner.type === 'success',
            'sikds-toast--danger':  banner.type === 'danger',
            'sikds-toast--info':    !['success','danger'].includes(banner.type),
         }"
         :role="banner.type === 'danger' ? 'alert' : 'status'">
        <span class="sikds-toast-icon">
            <i class="fa-solid"
               :class="{
                    'fa-circle-check':        banner.type === 'success',
                    'fa-circle-exclamation':  banner.type === 'danger',
                    'fa-circle-info':         !['success','danger'].includes(banner.type),
               }"></i>
        </span>
        <div class="sikds-toast-body">
            <p class="sikds-toast-title"
               x-text="banner.type === 'danger' ? @js(__('Action impossible')) : (banner.type === 'success' ? @js(__('Opération réussie')) : @js(__('Information')))"></p>
            <p class="sikds-toast-message" x-text="banner.message"></p>
        </div>
        <button type="button" @click="banner.message = ''" class="sikds-toast-dismiss" aria-label="{{ __('Fermer') }}">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Aperçu rapide avant la fiche document (portalled to <body>) --}}
    <template x-teleport="body">
    <div
        x-show="previewOpen"
        x-cloak
        class="sikds-docs-preview-backdrop fixed inset-0 z-[1000] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sikds-doc-preview-title"
    >
        <div class="sikds-docs-preview-overlay absolute inset-0 bg-slate-900/50" @click="closePreview()" aria-hidden="true"></div>
        <div
            class="sikds-docs-preview-panel relative z-10 flex max-h-[min(90vh,640px)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            @click.stop
        >
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Aperçu du document') }}</p>
                    <h2 id="sikds-doc-preview-title" class="mt-1 text-lg font-semibold leading-snug text-slate-900" x-text="previewDoc?.title"></h2>
                    <p class="mt-0.5 text-sm text-slate-600" x-text="previewDoc?.reference"></p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="closePreview()"
                    aria-label="{{ __('Fermer l\'aperçu') }}"
                >
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                <template x-if="previewDoc">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="sikds-status" :class="previewDoc.status_badge_class">
                                <i class="sikds-status-icon" :class="previewDoc.status_icon"></i>
                                <span x-text="previewDoc.status_label"></span>
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Public cible') }}</p>
                            <p class="mt-1 text-sm text-slate-800" x-text="previewDoc.target_audience"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __("Date d'émission") }}</p>
                            <p class="mt-1 text-sm text-slate-800" x-text="previewDoc.issue_date"></p>
                        </div>
                        <div x-show="previewDoc.tags_full && previewDoc.tags_full.length">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tags') }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="tag in previewDoc.tags_full" :key="tag.id">
                                    <span class="sikds-tag sikds-tag--table" :style="tag.style" x-text="tag.label"></span>
                                </template>
                            </div>
                        </div>
                        <div x-show="previewDoc.description_excerpt">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</p>
                            <p class="mt-1 text-sm leading-relaxed text-slate-700" x-text="previewDoc.description_excerpt"></p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3">
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    @click="closePreview()"
                >
                    {{ __('Fermer') }}
                </button>
                <a
                    :href="previewDoc?.show_url"
                    class="inline-flex items-center gap-2 rounded-lg bg-[var(--sikds-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
                >
                    <span>{{ __('Ouvrir la fiche complète') }}</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </div>
    </template>

    @can('document.create')
        <div class="flex justify-end mb-5">
            <a href="{{ route('documents.create') }}" class="sikds-btn-upload">
                <i class="fa-solid fa-plus"></i>
                <span>{{ __('Téléverser un Document') }}</span>
            </a>
        </div>
    @endcan

    @php
        $selectedStatus = $filters['status'] ?? [];
        $selectedTags = $filters['tags'] ?? [];
        $selectedAudience = $filters['audience'] ?? [];
        $hasDocumentFilters = filled($filters['q'] ?? null)
            || filled($filters['date_from'] ?? null)
            || filled($filters['date_to'] ?? null)
            || ! empty($selectedStatus)
            || ! empty($selectedTags)
            || ! empty($selectedAudience);
    @endphp

    <form method="GET" action="{{ route('documents.index') }}" class="sikds-docs-toolbar">
        <div class="sikds-docs-search">
            <i class="fa-solid fa-magnifying-glass sikds-docs-search-icon"></i>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Rechercher par titre ou référence...') }}" class="sikds-docs-search-input">
        </div>

        <div class="sikds-docs-toolbar-right">
            <div class="sikds-docs-daterange">
                <i class="fa-regular fa-calendar sikds-docs-daterange-icon"></i>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="sikds-docs-date-input">
                <span class="sikds-docs-date-sep">–</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="sikds-docs-date-input">
            </div>

            <div class="sikds-docs-filter-wrap">
                <button
                    type="button"
                    class="sikds-docs-filter-btn"
                    @click="filtersOpen = !filtersOpen"
                    :aria-expanded="filtersOpen.toString()"
                    aria-haspopup="true"
                >
                    <i class="fa-solid fa-filter"></i>
                    <span>{{ __('Filtres') }}</span>
                    <i class="fa-solid fa-angle-down" :class="{ 'rotate-180': filtersOpen }"></i>
                </button>

                <div x-show="filtersOpen" x-transition.origin.top.right @click.outside="filtersOpen = false" class="sikds-docs-filter-panel" x-cloak>
                    <div class="sikds-docs-filter-section">
                        <p class="sikds-docs-filter-title">{{ __('Statut') }}</p>
                        <div class="sikds-docs-filter-options">
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="active" {{ in_array('active', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--active">
                                    <i class="fa-regular fa-circle-check sikds-status-icon"></i>
                                    {{ __('Actif') }}
                                </span>
                            </label>
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="draft" {{ in_array('draft', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--draft">
                                    <i class="fa-solid fa-gear sikds-status-icon"></i>
                                    {{ __('Brouillon') }}
                                </span>
                            </label>
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="archived" {{ in_array('archived', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--archived">
                                    <i class="fa-solid fa-box-archive sikds-status-icon"></i>
                                    {{ __('Archivé') }}
                                </span>
                            </label>
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="deleted" {{ in_array('deleted', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--deleted">
                                    <i class="fa-regular fa-circle-xmark sikds-status-icon"></i>
                                    {{ __('Supprimé') }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="sikds-docs-filter-section">
                        <p class="sikds-docs-filter-title">{{ __('Tags') }}</p>
                        <div class="sikds-docs-filter-tags">
                            @foreach ($availableTags as $tag)
                                <label class="sikds-docs-filter-check">
                                    <input type="checkbox" name="tags[]" value="{{ $tag['label'] }}" {{ in_array($tag['label'], $selectedTags, true) ? 'checked' : '' }}>
                                    <span class="sikds-tag sikds-tag--table" style="{{ $tag['style'] }}">{{ $tag['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="sikds-docs-filter-section">
                        <p class="sikds-docs-filter-title">{{ __('Public Cible') }}</p>
                        <div class="sikds-docs-filter-options sikds-docs-filter-options--plain">
                            <label class="sikds-docs-filter-check"><input type="checkbox" name="audience[]" value="Toutes les institutions" {{ in_array('Toutes les institutions', $selectedAudience, true) ? 'checked' : '' }}><span>{{ __('Toutes les institutions') }}</span></label>
                            <label class="sikds-docs-filter-check"><input type="checkbox" name="audience[]" value="Universités" {{ in_array('Universités', $selectedAudience, true) ? 'checked' : '' }}><span>{{ __('Universités') }}</span></label>
                            <label class="sikds-docs-filter-check"><input type="checkbox" name="audience[]" value="Cabinet du Ministre" {{ in_array('Cabinet du Ministre', $selectedAudience, true) ? 'checked' : '' }}><span>{{ __('Cabinet du Ministre') }}</span></label>
                        </div>
                    </div>

                    <div class="sikds-docs-filter-actions">
                        <a href="{{ route('documents.index') }}" class="sikds-docs-filter-clear">{{ __('Réinitialiser') }}</a>
                        <button type="submit" class="sikds-docs-filter-apply" @click="filtersOpen = false">{{ __('Appliquer') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="sikds-docs-table-wrap">
        <table class="sikds-docs-table">
            <thead>
                <tr>
                    <th class="sikds-th-title">{{ __('Titre & Référence') }}</th>
                    <th class="sikds-th-status">{{ __('Statut') }}</th>
                    <th class="sikds-th-tags">{{ __('Tags') }}</th>
                    <th class="sikds-th-audience">{{ __('Public Cible') }}</th>
                    <th class="sikds-th-date">{{ __("Date d'Émission") }}</th>
                    <th class="sikds-th-actions">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                    <tr>
                        <td>
                            <div class="sikds-docs-title-cell">
                                <div class="sikds-docs-icon-wrap">
                                    <i class="fa-solid fa-file-lines sikds-docs-file-icon"></i>
                                </div>
                                <div>
                                    <p class="sikds-docs-title">{{ $doc['title'] }}</p>
                                    <p class="sikds-docs-ref">{{ $doc['reference'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'active'   => ['label' => __('Actif'),     'class' => 'sikds-status--active',   'icon' => 'fa-regular fa-circle-check'],
                                    'draft'    => ['label' => __('Brouillon'), 'class' => 'sikds-status--draft',    'icon' => 'fa-solid fa-gear'],
                                    'archived' => ['label' => __('Archivé'),   'class' => 'sikds-status--archived', 'icon' => 'fa-solid fa-box-archive'],
                                    'deleted'  => ['label' => __('Supprimé'),  'class' => 'sikds-status--deleted',  'icon' => 'fa-regular fa-circle-xmark'],
                                ];
                                $s = $statusMap[$doc['status']] ?? $statusMap['draft'];
                            @endphp
                            <span class="sikds-status {{ $s['class'] }}">
                                <i class="{{ $s['icon'] }} sikds-status-icon"></i>
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td>
                            <div class="sikds-docs-tags">
                                @foreach ($doc['tags'] as $tag)
                                    <span class="sikds-tag sikds-tag--table" style="{{ $tag['style'] }}">{{ $tag['label'] }}</span>
                                @endforeach
                                @if ($doc['extra_tags'] > 0)
                                    <span class="sikds-docs-extra-tags">+{{ $doc['extra_tags'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="sikds-docs-audience">{{ $doc['target_audience'] }}</td>
                        <td class="sikds-docs-date">{{ $doc['issue_date'] }}</td>
                        <td>
                            <div class="sikds-docs-actions">
                                @foreach ($doc['actions'] as $action)
                                    @switch($action)
                                        @case('view')
                                            <button type="button" class="sikds-docs-action-btn" title="{{ __('Consulter (aperçu)') }}" aria-label="{{ __('Consulter (aperçu)') }}" @click.prevent="openPreview(@js($doc))">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            @break
                                        @case('edit')
                                            <a href="{{ $doc['edit_url'] }}" class="sikds-docs-action-btn" title="{{ __('Modifier') }}" aria-label="{{ __('Modifier') }}">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            @break
                                        @case('archive')
                                            <button type="button" class="sikds-docs-action-btn" title="{{ __('Archiver') }}" aria-label="{{ __('Archiver') }}" @click="performAction(@js($doc['archive_url']), 'POST', @js(__('Document archivé.')))">
                                                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-box-archive'"></i>
                                            </button>
                                            @break
                                        @case('download')
                                            <button type="button" class="sikds-docs-action-btn" title="{{ __('Télécharger') }}" aria-label="{{ __('Télécharger') }}" @click="$dispatch('open-download-modal', { downloadUrl: @js($doc['download_url']) })">
                                                <i class="fa-solid fa-download"></i>
                                            </button>
                                            @break
                                        @case('forward')
                                            <button type="button" class="sikds-docs-action-btn" title="{{ __('Transférer') }}" aria-label="{{ __('Transférer') }}" @click="$dispatch('open-forward-modal', { forwardUrl: @js($doc['forward_url']), reference: @js($doc['reference']), title: @js($doc['title']) })">
                                                <i class="fa-solid fa-share-from-square"></i>
                                            </button>
                                            @break
                                        @case('publish')
                                            <button type="button" class="sikds-docs-action-btn" title="{{ __('Publier') }}" aria-label="{{ __('Publier') }}" @click="performAction(@js($doc['publish_url']), 'POST', @js(__('Document publié.')))">
                                                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-paper-plane'"></i>
                                            </button>
                                            @break
                                        @case('delete')
                                            <button type="button" class="sikds-docs-action-btn sikds-docs-action-btn--danger" title="{{ __('Supprimer') }}" aria-label="{{ __('Supprimer') }}" @click="openDeleteModal(@js($doc))">
                                                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-trash-can'"></i>
                                            </button>
                                            @break
                                        @case('restore')
                                            <button type="button" class="sikds-docs-action-btn" title="{{ __('Restaurer') }}" aria-label="{{ __('Restaurer') }}" @click="performAction(@js($doc['restore_url']), 'POST', @js(__('Document restauré.')))">
                                                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-rotate-left'"></i>
                                            </button>
                                            @break
                                    @endswitch
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8">
                            <x-empty-state
                                icon="fa-regular fa-file-lines"
                                :title="$hasDocumentFilters ? __('Aucun document ne correspond aux filtres sélectionnés.') : __('Aucun document à afficher.')"
                                :description="$hasDocumentFilters ? __('Modifiez ou réinitialisez les filtres pour élargir les résultats.') : __('Téléversez un document PDF pour démarrer le cycle de gestion documentaire.')"
                                class="shadow-none"
                            >
                                <x-slot:action>
                                    @if ($hasDocumentFilters)
                                        <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-2 rounded-[10px] border border-black/10 bg-white px-4 py-2 text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.03]">
                                            <i class="fa-solid fa-rotate-left text-xs"></i>
                                            {{ __('Réinitialiser les filtres') }}
                                        </a>
                                    @else
                                        @can('document.create')
                                            <a href="{{ route('documents.create') }}" class="inline-flex items-center gap-2 rounded-[10px] bg-[#1c398e] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#163171]">
                                                <i class="fa-solid fa-plus text-xs"></i>
                                                {{ __('Téléverser un Document') }}
                                            </a>
                                        @endcan
                                    @endif
                                </x-slot:action>
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sikds-docs-pagination">
        <span class="sikds-docs-pagination-info">
            {{ __('Affichage de') }} {{ $documents->firstItem() ?? 0 }}-{{ $documents->lastItem() ?? 0 }} {{ __('sur') }} {{ $documents->total() }} {{ __('documents') }}
        </span>
        <div class="sikds-docs-pagination-btns">
            @if ($documents->onFirstPage())
                <button type="button" class="sikds-docs-page-btn" disabled>{{ __('Précédent') }}</button>
            @else
                <a href="{{ $documents->previousPageUrl() }}" class="sikds-docs-page-btn">{{ __('Précédent') }}</a>
            @endif

            @if ($documents->hasMorePages())
                <a href="{{ $documents->nextPageUrl() }}" class="sikds-docs-page-btn">{{ __('Suivant') }}</a>
            @else
                <button type="button" class="sikds-docs-page-btn" disabled>{{ __('Suivant') }}</button>
            @endif
        </div>
    </div>

    {{-- Confirmation modal: Delete (dashboard) --}}
    <div x-show="modal === 'delete'" x-transition.opacity class="sikds-doc-modal-overlay" @click.self="modal = null" x-cloak>
        <div class="sikds-doc-modal">
            <button type="button" class="sikds-doc-modal-close" @click="modal = null" aria-label="{{ __('Fermer') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-doc-modal-head">
                <div class="sikds-doc-modal-icon sikds-doc-modal-icon--delete">
                    <i class="fa-regular fa-circle-exclamation"></i>
                </div>
                <div>
                    <h3 class="sikds-doc-modal-title">{{ __('Supprimer le Document') }}</h3>
                    <p class="sikds-doc-modal-subtitle">{{ __('Action irréversible') }}</p>
                </div>
            </div>

            <p class="sikds-doc-modal-text">
                {{ __('Êtes-vous sûr de vouloir supprimer définitivement le document') }}
                "<strong x-text="pendingDeleteDoc?.title ?? ''"></strong>" ?
            </p>

            <div class="sikds-doc-modal-actions">
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--cancel" @click="modal = null">
                    {{ __('Annuler') }}
                </button>
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--delete" @click="confirmDelete()">
                    <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-trash-can'"></i>
                    {{ __('Supprimer') }}
                </button>
            </div>
        </div>
    </div>

    </div>


    @include('documents.partials.download_modal')

    @if (! empty($canForward))
        @include('documents.partials.forward_modal')
    @endif

@endsection
