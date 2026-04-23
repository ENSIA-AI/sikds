@extends('layouts.app')
@section('page_title', 'Gestion des Documents')
@section('page_subtitle', 'Gérer le cycle de vie des documents institutionnels')
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
               x-text="banner.type === 'danger' ? 'Action impossible' : (banner.type === 'success' ? 'Opération réussie' : 'Information')"></p>
            <p class="sikds-toast-message" x-text="banner.message"></p>
        </div>
        <button type="button" @click="banner.message = ''" class="sikds-toast-dismiss" aria-label="Fermer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Aperçu rapide avant la fiche document --}}
    <div
        x-show="previewOpen"
        x-cloak
        class="sikds-docs-preview-backdrop fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sikds-doc-preview-title"
    >
        <div class="sikds-docs-preview-overlay absolute inset-0 bg-black/40" @click="closePreview()" aria-hidden="true"></div>
        <div
            class="sikds-docs-preview-panel relative z-10 flex max-h-[min(90vh,640px)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            @click.stop
        >
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Aperçu du document</p>
                    <h2 id="sikds-doc-preview-title" class="mt-1 text-lg font-semibold leading-snug text-slate-900" x-text="previewDoc?.title"></h2>
                    <p class="mt-0.5 text-sm text-slate-600" x-text="previewDoc?.reference"></p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="closePreview()"
                    aria-label="Fermer l'aperçu"
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Public cible</p>
                            <p class="mt-1 text-sm text-slate-800" x-text="previewDoc.target_audience"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date d'émission</p>
                            <p class="mt-1 text-sm text-slate-800" x-text="previewDoc.issue_date"></p>
                        </div>
                        <div x-show="previewDoc.tags_full && previewDoc.tags_full.length">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tags</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="tag in previewDoc.tags_full" :key="tag.id">
                                    <span class="sikds-tag sikds-tag--table" :style="tag.style" x-text="tag.label"></span>
                                </template>
                            </div>
                        </div>
                        <div x-show="previewDoc.description_excerpt">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</p>
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
                    Fermer
                </button>
                <a
                    :href="previewDoc?.show_url"
                    class="inline-flex items-center gap-2 rounded-lg bg-[var(--sikds-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
                >
                    <span>Ouvrir la fiche complète</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="flex justify-end mb-5">
        <a href="{{ route('documents.create') }}" class="sikds-btn-upload">
            <i class="fa-solid fa-plus"></i>
            <span>Téléverser un Document</span>
        </a>
    </div>

    @php
        $selectedStatus = $filters['status'] ?? [];
        $selectedTags = $filters['tags'] ?? [];
        $selectedAudience = $filters['audience'] ?? [];
    @endphp

    <form method="GET" action="{{ route('documents.index') }}" class="sikds-docs-toolbar">
        <div class="sikds-docs-search">
            <i class="fa-solid fa-magnifying-glass sikds-docs-search-icon"></i>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher par titre ou référence..." class="sikds-docs-search-input">
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
                    <span>Filtres</span>
                    <i class="fa-solid fa-angle-down" :class="{ 'rotate-180': filtersOpen }"></i>
                </button>

                <div x-show="filtersOpen" x-transition.origin.top.right @click.outside="filtersOpen = false" class="sikds-docs-filter-panel" x-cloak>
                    <div class="sikds-docs-filter-section">
                        <p class="sikds-docs-filter-title">Statut</p>
                        <div class="sikds-docs-filter-options">
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="active" {{ in_array('active', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--active">
                                    <i class="fa-regular fa-circle-check sikds-status-icon"></i>
                                    Actif
                                </span>
                            </label>
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="draft" {{ in_array('draft', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--draft">
                                    <i class="fa-solid fa-gear sikds-status-icon"></i>
                                    Brouillon
                                </span>
                            </label>
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="archived" {{ in_array('archived', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--archived">
                                    <i class="fa-solid fa-box-archive sikds-status-icon"></i>
                                    Archivé
                                </span>
                            </label>
                            <label class="sikds-docs-filter-check">
                                <input type="checkbox" name="status[]" value="deleted" {{ in_array('deleted', $selectedStatus, true) ? 'checked' : '' }}>
                                <span class="sikds-status sikds-status--deleted">
                                    <i class="fa-regular fa-circle-xmark sikds-status-icon"></i>
                                    Supprimé
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="sikds-docs-filter-section">
                        <p class="sikds-docs-filter-title">Tags</p>
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
                        <p class="sikds-docs-filter-title">Public Cible</p>
                        <div class="sikds-docs-filter-options sikds-docs-filter-options--plain">
                            <label class="sikds-docs-filter-check"><input type="checkbox" name="audience[]" value="Toutes les institutions" {{ in_array('Toutes les institutions', $selectedAudience, true) ? 'checked' : '' }}><span>Toutes les institutions</span></label>
                            <label class="sikds-docs-filter-check"><input type="checkbox" name="audience[]" value="Universités" {{ in_array('Universités', $selectedAudience, true) ? 'checked' : '' }}><span>Universités</span></label>
                            <label class="sikds-docs-filter-check"><input type="checkbox" name="audience[]" value="Cabinet du Ministre" {{ in_array('Cabinet du Ministre', $selectedAudience, true) ? 'checked' : '' }}><span>Cabinet du Ministre</span></label>
                        </div>
                    </div>

                    <div class="sikds-docs-filter-actions">
                        <a href="{{ route('documents.index') }}" class="sikds-docs-filter-clear">Réinitialiser</a>
                        <button type="submit" class="sikds-docs-filter-apply" @click="filtersOpen = false">Appliquer</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="sikds-docs-table-wrap">
        <table class="sikds-docs-table">
            <thead>
                <tr>
                    <th class="sikds-th-title">Titre & Référence</th>
                    <th class="sikds-th-status">Statut</th>
                    <th class="sikds-th-tags">Tags</th>
                    <th class="sikds-th-audience">Public Cible</th>
                    <th class="sikds-th-date">Date d'Émission</th>
                    <th class="sikds-th-actions">Actions</th>
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
                                    'active'   => ['label' => 'Actif',     'class' => 'sikds-status--active',   'icon' => 'fa-regular fa-circle-check'],
                                    'draft'    => ['label' => 'Brouillon', 'class' => 'sikds-status--draft',    'icon' => 'fa-solid fa-gear'],
                                    'archived' => ['label' => 'Archivé',   'class' => 'sikds-status--archived', 'icon' => 'fa-solid fa-box-archive'],
                                    'deleted'  => ['label' => 'Supprimé',  'class' => 'sikds-status--deleted',  'icon' => 'fa-regular fa-circle-xmark'],
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
                                            <button type="button" class="sikds-docs-action-btn" title="Consulter (aperçu)" aria-label="Consulter (aperçu)" @click.prevent="openPreview(@js($doc))">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            @break
                                        @case('edit')
                                            <a href="{{ $doc['edit_url'] }}" class="sikds-docs-action-btn" title="Modifier" aria-label="Modifier">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            @break
                                        @case('archive')
                                            <button type="button" class="sikds-docs-action-btn" title="Archiver" aria-label="Archiver" @click="performAction(@js($doc['archive_url']), 'POST', 'Document archivé.')">
                                                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-box-archive'"></i>
                                            </button>
                                            @break
                                        @case('download')
                                            <a href="{{ $doc['download_url'] }}" class="sikds-docs-action-btn" title="Télécharger" aria-label="Télécharger">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            @break
                                        @case('publish')
                                            <button type="button" class="sikds-docs-action-btn" title="Publier" aria-label="Publier" @click="performAction(@js($doc['publish_url']), 'POST', 'Document publié.')">
                                                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-paper-plane'"></i>
                                            </button>
                                            @break
                                        @case('delete')
                                            <button type="button" class="sikds-docs-action-btn sikds-docs-action-btn--danger" title="Supprimer" aria-label="Supprimer" @click="performAction(@js($doc['delete_url']), 'DELETE', 'Document supprimé.')">
                                                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-trash-can'"></i>
                                            </button>
                                            @break
                                        @case('restore')
                                            <button type="button" class="sikds-docs-action-btn" title="Restaurer" aria-label="Restaurer" @click="performAction(@js($doc['restore_url']), 'POST', 'Document restauré.')">
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
                        <td colspan="6" class="text-center py-8 text-sm sikds-muted-text">
                            Aucun document ne correspond aux filtres sélectionnés.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sikds-docs-pagination">
        <span class="sikds-docs-pagination-info">
            Affichage de {{ $documents->firstItem() ?? 0 }}-{{ $documents->lastItem() ?? 0 }} sur {{ $documents->total() }} documents
        </span>
        <div class="sikds-docs-pagination-btns">
            @if ($documents->onFirstPage())
                <button type="button" class="sikds-docs-page-btn" disabled>Précédent</button>
            @else
                <a href="{{ $documents->previousPageUrl() }}" class="sikds-docs-page-btn">Précédent</a>
            @endif

            @if ($documents->hasMorePages())
                <a href="{{ $documents->nextPageUrl() }}" class="sikds-docs-page-btn">Suivant</a>
            @else
                <button type="button" class="sikds-docs-page-btn" disabled>Suivant</button>
            @endif
        </div>
    </div>

    </div>

    <script>
        function documentListPage(config) {
            return {
                filtersOpen: false,
                previewOpen: false,
                previewDoc: null,
                loading: false,
                banner: { message: '', type: 'info' },
                init() {
                    const message = window.sessionStorage.getItem('documents-success-message');
                    if (message) {
                        this.banner = { message, type: 'success' };
                        window.sessionStorage.removeItem('documents-success-message');
                    }
                },
                handleEscape() {
                    if (this.previewOpen) {
                        this.closePreview();
                        return;
                    }
                    this.filtersOpen = false;
                },
                openPreview(doc) {
                    this.previewDoc = doc;
                    this.previewOpen = true;
                    document.documentElement.classList.add('overflow-hidden');
                },
                closePreview() {
                    this.previewOpen = false;
                    this.previewDoc = null;
                    document.documentElement.classList.remove('overflow-hidden');
                },
                async performAction(url, method, successMessage) {
                    if (this.loading) return;

                    this.loading = true;
                    this.banner = { message: '', type: 'info' };

                    try {
                        const response = await fetch(url, {
                            method,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            credentials: 'same-origin',
                        });

                        if (response.redirected) {
                            throw new Error('La requête a été redirigée par le serveur. Vérifiez votre session.');
                        }

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(payload.message || 'Une erreur est survenue.');
                        }

                        this.banner = { message: successMessage, type: 'success' };
                        window.location.reload();
                    } catch (error) {
                        this.banner = { message: error.message || 'Action impossible.', type: 'danger' };
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>

@endsection
