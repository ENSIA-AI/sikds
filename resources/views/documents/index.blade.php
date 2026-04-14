@extends('layouts.app')
@section('page_title', 'Gestion des Documents')
@section('page_subtitle', 'Gérer le cycle de vie des documents institutionnels')
@section('content')

    <div class="flex justify-end mb-5">
        <a href="{{ route('documents.create') }}" class="sikds-btn-upload">
            <i class="fa-solid fa-plus"></i>
            <span>Téléverser un Document</span>
        </a>
    </div>

    <div class="sikds-docs-toolbar">
        <div class="sikds-docs-search">
            <i class="fa-solid fa-magnifying-glass sikds-docs-search-icon"></i>
            <input type="text" placeholder="Rechercher par titre ou référence..." class="sikds-docs-search-input">
        </div>

        <div class="sikds-docs-toolbar-right">
            <div class="sikds-docs-daterange">
                <i class="fa-regular fa-calendar sikds-docs-daterange-icon"></i>
                <input type="text" value="2026/01/23" class="sikds-docs-date-input" readonly>
                <span class="sikds-docs-date-sep">–</span>
                <input type="text" value="2026/01/23" class="sikds-docs-date-input" readonly>
            </div>

            <button type="button" class="sikds-docs-filter-btn">
                <i class="fa-solid fa-filter"></i>
                <span>Filtres</span>
            </button>
        </div>
    </div>

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
                @foreach ($documents as $doc)
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
                                            <a href="{{ route('documents.show', $doc['reference']) }}" class="sikds-docs-action-btn" title="Consulter" aria-label="Consulter">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                            @break
                                        @case('edit')
                                            <button type="button" class="sikds-docs-action-btn" title="Modifier" aria-label="Modifier">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            @break
                                        @case('download')
                                            <button type="button" class="sikds-docs-action-btn" title="Télécharger" aria-label="Télécharger">
                                                <i class="fa-solid fa-download"></i>
                                            </button>
                                            @break
                                        @case('copy')
                                            <button type="button" class="sikds-docs-action-btn" title="Copier" aria-label="Copier">
                                                <i class="fa-regular fa-copy"></i>
                                            </button>
                                            @break
                                        @case('delete')
                                            <button type="button" class="sikds-docs-action-btn sikds-docs-action-btn--danger" title="Supprimer" aria-label="Supprimer">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                            @break
                                        @case('restore')
                                            <button type="button" class="sikds-docs-action-btn" title="Restaurer" aria-label="Restaurer">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                            @break
                                    @endswitch
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="sikds-docs-pagination">
        <span class="sikds-docs-pagination-info">
            Affichage de 1-{{ count($documents) }} sur {{ count($documents) }} documents
        </span>
        <div class="sikds-docs-pagination-btns">
            <button type="button" class="sikds-docs-page-btn" disabled>Précédent</button>
            <button type="button" class="sikds-docs-page-btn" disabled>Suivant</button>
        </div>
    </div>

    <a href="#" class="sikds-fab" aria-label="Notifications">
        <span class="relative inline-flex">
            <img src="/bell.svg" alt="" class="h-5 w-5">
            <span class="sikds-fab-dot" aria-hidden="true"></span>
        </span>
    </a>

@endsection
