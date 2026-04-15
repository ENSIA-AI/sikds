@extends('layouts.app')
@php
    $activeNav = 'indexing';
@endphp
@section('page_title', 'Indexation des Documents')
@section('page_subtitle', "Suivi des traitements et statut d'indexation documentaire")
@section('content')

    <div class="flex justify-between items-center mb-5 gap-3 flex-wrap">
        <p class="text-sm sikds-muted-text">Mise à jour automatique toutes les 10 secondes.</p>
        <a href="{{ url()->current() }}" class="sikds-doc-edit-btn sikds-doc-edit-btn--cancel">
            <i class="fa-solid fa-rotate-right"></i>
            Rafraîchir
        </a>
    </div>

    <div class="sikds-docs-table-wrap">
        <table class="sikds-docs-table">
            <thead>
                <tr>
                    <th class="sikds-th-title">Titre & Référence</th>
                    <th class="sikds-th-status">Statut</th>
                    <th class="sikds-th-date">Chunks</th>
                    <th class="sikds-th-date">Dernière mise à jour</th>
                    <th class="sikds-th-actions">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                    @php
                        $statusMap = [
                            'pending' => ['label' => 'En attente', 'class' => 'sikds-status--draft', 'icon' => 'fa-regular fa-clock'],
                            'processing' => ['label' => 'Traitement', 'class' => 'sikds-status--archived', 'icon' => 'fa-solid fa-spinner'],
                            'indexed' => ['label' => 'Indexé', 'class' => 'sikds-status--active', 'icon' => 'fa-regular fa-circle-check'],
                            'failed' => ['label' => 'Échec', 'class' => 'sikds-status--deleted', 'icon' => 'fa-regular fa-circle-xmark'],
                        ];
                        $s = $statusMap[$doc->indexing_status] ?? $statusMap['pending'];
                    @endphp
                    <tr>
                        <td>
                            <div class="sikds-docs-title-cell">
                                <div class="sikds-docs-icon-wrap">
                                    <i class="fa-solid fa-file-lines sikds-docs-file-icon"></i>
                                </div>
                                <div>
                                    <p class="sikds-docs-title">{{ $doc->title }}</p>
                                    <p class="sikds-docs-ref">{{ $doc->reference_number }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="sikds-status {{ $s['class'] }}">
                                <i class="{{ $s['icon'] }} sikds-status-icon {{ $doc->indexing_status === 'processing' ? 'fa-spin' : '' }}"></i>
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td class="sikds-docs-date">{{ number_format((int) $doc->chunks_count) }}</td>
                        <td class="sikds-docs-date">{{ $doc->updated_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            @if ($doc->indexing_status === 'failed')
                                <form method="POST" action="{{ route('indexing.retry', $doc) }}">
                                    @csrf
                                    <button type="submit" class="sikds-doc-edit-btn sikds-doc-edit-btn--save">
                                        <i class="fa-solid fa-rotate"></i>
                                        Réessayer
                                    </button>
                                </form>
                            @else
                                <span class="text-xs sikds-muted-text">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-sm sikds-muted-text">
                            Aucun document à afficher.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sikds-docs-pagination">
        <span class="sikds-docs-pagination-info">
            Affichage de {{ $documents->firstItem() ?? 0 }}-{{ $documents->lastItem() ?? 0 }} sur {{ number_format($documents->total()) }} documents
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

    <script>
        (function () {
            try {
                setInterval(function () {
                    window.location.reload();
                }, 10000);
            } catch (e) {}
        })();
    </script>
@endsection

