@extends('layouts.app')
@section('page_title', 'Gestion des Documents')
@section('page_subtitle', 'Gérer le cycle de vie des documents institutionnels')
@section('content')

    <div
        x-data="documentListPage({
            csrfToken: @js(csrf_token()),
        })"
        @keydown.escape.window="filtersOpen = false"
        class="relative"
    >

    <div x-show="banner.message" x-cloak class="sikds-alert" :class="banner.type === 'danger' ? 'sikds-alert--danger' : 'sikds-alert--info'">
        <p class="sikds-alert-message" x-text="banner.message"></p>
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
                                    <span class="sikds-tag sikds-tag--table {{ $tag['class'] }}">{{ $tag['label'] }}</span>
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
                                    <span class="sikds-tag sikds-tag--table {{ $tag['class'] }}">{{ $tag['label'] }}</span>
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
                                            <a href="{{ $doc['show_url'] }}" class="sikds-docs-action-btn" title="Consulter" aria-label="Consulter">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                            @break
                                        @case('edit')
                                            <a href="{{ $doc['edit_url'] }}" class="sikds-docs-action-btn" title="Modifier" aria-label="Modifier">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
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
                loading: false,
                banner: { message: '', type: 'info' },
                init() {
                    const message = window.sessionStorage.getItem('documents-success-message');
                    if (message) {
                        this.banner = { message, type: 'info' };
                        window.sessionStorage.removeItem('documents-success-message');
                    }
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

                        this.banner = { message: successMessage, type: 'info' };
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
