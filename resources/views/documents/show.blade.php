@extends('layouts.app')
@section('page_title', 'Gestion des Documents')
@section('page_subtitle', 'Gérer le cycle de vie des documents institutionnels')
@section('content')

<div class="sikds-doc-detail" x-data="{ activeTab: 'overview', modal: null }">

    {{-- Top bar: back link + action buttons --}}
    <div class="sikds-doc-topbar">
        <a href="{{ route('documents.index') }}" class="sikds-doc-back">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Retour aux documents</span>
        </a>

        <div class="sikds-doc-actions">
            <button type="button" class="sikds-doc-action-btn sikds-doc-action-btn--default">
                <i class="fa-solid fa-download"></i>
                <span>Télécharger</span>
            </button>
            <a href="{{ route('documents.edit', $document['reference']) }}" class="sikds-doc-action-btn sikds-doc-action-btn--default">
                <i class="fa-solid fa-pen"></i>
                <span>Modifier</span>
            </a>
            <button type="button" class="sikds-doc-action-btn sikds-doc-action-btn--warn" @click="modal = 'archive'">
                <i class="fa-solid fa-box-archive"></i>
                <span>Archiver</span>
            </button>
            <button type="button" class="sikds-doc-action-btn sikds-doc-action-btn--danger" @click="modal = 'delete'">
                <i class="fa-regular fa-trash-can"></i>
                <span>Supprimer</span>
            </button>
        </div>
    </div>

    {{-- Title row --}}
    <div class="sikds-doc-title-row">
        <div>
            <h2 class="sikds-doc-title">{{ $document['title'] }}</h2>
            <p class="sikds-doc-ref">{{ $document['reference'] }}</p>
        </div>
        @php
            $statusMap = [
                'active'   => ['label' => 'Actif',     'class' => 'sikds-status--active',   'icon' => 'fa-regular fa-circle-check'],
                'draft'    => ['label' => 'Brouillon', 'class' => 'sikds-status--draft',    'icon' => 'fa-solid fa-gear'],
                'archived' => ['label' => 'Archivé',   'class' => 'sikds-status--archived', 'icon' => 'fa-solid fa-box-archive'],
                'deleted'  => ['label' => 'Supprimé',  'class' => 'sikds-status--deleted',  'icon' => 'fa-regular fa-circle-xmark'],
            ];
            $s = $statusMap[$document['status']] ?? $statusMap['draft'];
        @endphp
        <span class="sikds-status {{ $s['class'] }}">
            <i class="{{ $s['icon'] }} sikds-status-icon"></i>
            {{ $s['label'] }}
        </span>
    </div>

    {{-- Tab navigation --}}
    <div class="sikds-doc-tabs">
        <button type="button"
                class="sikds-doc-tab"
                :class="activeTab === 'overview' ? 'sikds-doc-tab--active' : 'sikds-doc-tab--idle'"
                @click="activeTab = 'overview'">
            Vue d'ensemble
        </button>
        <button type="button"
                class="sikds-doc-tab"
                :class="activeTab === 'versions' ? 'sikds-doc-tab--active' : 'sikds-doc-tab--idle'"
                @click="activeTab = 'versions'">
            Historique des Versions
        </button>
        <button type="button"
                class="sikds-doc-tab"
                :class="activeTab === 'downloads' ? 'sikds-doc-tab--active' : 'sikds-doc-tab--idle'"
                @click="activeTab = 'downloads'">
            Historique de Téléchargement
        </button>
        <button type="button"
                class="sikds-doc-tab"
                :class="activeTab === 'activity' ? 'sikds-doc-tab--active' : 'sikds-doc-tab--idle'"
                @click="activeTab = 'activity'">
            Activité
        </button>
    </div>

    {{-- Tab: Vue d'ensemble --}}
    <div x-show="activeTab === 'overview'" class="sikds-doc-content">
        <div class="sikds-doc-grid">

            {{-- Left column --}}
            <div class="sikds-doc-col-left">

                {{-- Description card --}}
                <div class="sikds-doc-card">
                    <h3 class="sikds-doc-card-heading">Description</h3>
                    <p class="sikds-doc-card-text">{{ $document['description'] }}</p>
                </div>

                {{-- Metadata card --}}
                <div class="sikds-doc-card">
                    <h3 class="sikds-doc-card-heading">Métadonnées Complètes</h3>

                    <div class="sikds-doc-meta-grid">
                        {{-- General info --}}
                        <div class="sikds-doc-meta-col">
                            <p class="sikds-doc-meta-section-title">Informations Générales</p>

                            <div class="sikds-doc-meta-row">
                                <i class="fa-regular fa-hashtag sikds-doc-meta-icon"></i>
                                <div>
                                    <p class="sikds-doc-meta-label">Référence</p>
                                    <p class="sikds-doc-meta-value">{{ $document['reference'] }}</p>
                                </div>
                            </div>

                            <div class="sikds-doc-meta-row">
                                <i class="fa-regular fa-building sikds-doc-meta-icon"></i>
                                <div>
                                    <p class="sikds-doc-meta-label">Institution</p>
                                    <p class="sikds-doc-meta-value">{{ $document['institution'] }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Dates --}}
                        <div class="sikds-doc-meta-col">
                            <p class="sikds-doc-meta-section-title">Dates</p>

                            <div class="sikds-doc-meta-row">
                                <i class="fa-regular fa-calendar sikds-doc-meta-icon"></i>
                                <div>
                                    <p class="sikds-doc-meta-label">Date d'Émission</p>
                                    <p class="sikds-doc-meta-value">{{ $document['issue_date'] }}</p>
                                </div>
                            </div>

                            <div class="sikds-doc-meta-row">
                                <i class="fa-regular fa-calendar sikds-doc-meta-icon"></i>
                                <div>
                                    <p class="sikds-doc-meta-label">Date d'Effet</p>
                                    <p class="sikds-doc-meta-value">{{ $document['effective_date'] }}</p>
                                </div>
                            </div>

                            <div class="sikds-doc-meta-row">
                                <i class="fa-regular fa-calendar sikds-doc-meta-icon"></i>
                                <div>
                                    <p class="sikds-doc-meta-label">Date d'Expiration</p>
                                    <p class="sikds-doc-meta-value">{{ $document['expiry_date'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Public Cible banner --}}
                <div class="sikds-doc-audience-banner">
                    <i class="fa-solid fa-circle-info sikds-doc-audience-icon"></i>
                    <div>
                        <p class="sikds-doc-audience-title">Public Cible</p>
                        <p class="sikds-doc-audience-text">
                            Ce document est accessible à : <strong>{{ $document['audience'] }}</strong>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Right column --}}
            <div class="sikds-doc-col-right">

                {{-- Tags card --}}
                <div class="sikds-doc-card">
                    <h3 class="sikds-doc-card-heading sikds-doc-card-heading--with-icon">
                        <i class="fa-solid fa-tags"></i>
                        Tags
                    </h3>
                    <div class="sikds-doc-tags-wrap">
                        @foreach ($document['tags'] as $tag)
                            <span class="sikds-tag {{ $tag['class'] }}">{{ $tag['label'] }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Statistiques card --}}
                <div class="sikds-doc-card">
                    <h3 class="sikds-doc-card-heading">Statistiques</h3>

                    <div class="sikds-doc-stat-rows">
                        <div class="sikds-doc-stat-row">
                            <span class="sikds-doc-stat-label">
                                <i class="fa-regular fa-eye"></i> Vues
                            </span>
                            <span class="sikds-doc-stat-value">{{ $document['views'] }}</span>
                        </div>
                        <div class="sikds-doc-stat-row">
                            <span class="sikds-doc-stat-label">
                                <i class="fa-solid fa-download"></i> Téléchargements
                            </span>
                            <span class="sikds-doc-stat-value">{{ $document['downloads'] }}</span>
                        </div>
                        <div class="sikds-doc-stat-row">
                            <span class="sikds-doc-stat-label">
                                <i class="fa-solid fa-code-branch"></i> Version
                            </span>
                            <span class="sikds-doc-stat-value">{{ $document['version'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- Fichier card --}}
                <div class="sikds-doc-card">
                    <h3 class="sikds-doc-card-heading">Fichier</h3>

                    <div class="sikds-doc-file-rows">
                        <div class="sikds-doc-file-row">
                            <p class="sikds-doc-meta-label">Nom</p>
                            <p class="sikds-doc-meta-value">{{ $document['file_name'] }}</p>
                        </div>
                        <div class="sikds-doc-file-row">
                            <p class="sikds-doc-meta-label">Type</p>
                            <p class="sikds-doc-meta-value">{{ $document['file_type'] }}</p>
                        </div>
                        <div class="sikds-doc-file-row">
                            <p class="sikds-doc-meta-label">Taille</p>
                            <p class="sikds-doc-meta-value">{{ $document['file_size'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div x-show="activeTab === 'versions'" class="sikds-doc-content">
        <div class="sikds-doc-history-card">
            <div class="sikds-doc-history-head">
                <h3 class="sikds-doc-history-title">Historique des Versions</h3>
                <p class="sikds-doc-history-sub">Toutes les versions de ce document</p>
            </div>
            <div class="sikds-doc-history-list">
                @foreach ($document['versions'] as $version)
                    <div class="sikds-doc-history-row">
                        <div class="sikds-doc-history-row-main">
                            <div class="sikds-doc-history-icon-wrap sikds-doc-history-icon-wrap--version {{ $loop->first ? 'sikds-doc-history-icon-wrap--current' : '' }}">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="sikds-doc-history-content">
                                <div class="sikds-doc-history-line">
                                    <p class="sikds-doc-history-item-title">{{ $version['title'] }}</p>
                                    @if ($version['status'])
                                        <span class="sikds-doc-pill {{ $version['status_class'] }}">{{ $version['status'] }}</span>
                                    @endif
                                </div>
                                <p class="sikds-doc-history-item-meta">{{ $version['meta'] }}</p>
                                <p class="sikds-doc-history-item-desc">{{ $version['description'] }}</p>
                            </div>
                        </div>
                        <div class="sikds-doc-history-tools">
                            <button type="button" class="sikds-doc-tool-btn" aria-label="Consulter version">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                            <button type="button" class="sikds-doc-tool-btn" aria-label="Télécharger version">
                                <i class="fa-solid fa-download"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'downloads'" class="sikds-doc-content">
        <div class="sikds-doc-history-card">
            <div class="sikds-doc-history-head">
                <h3 class="sikds-doc-history-title">Historique de Téléchargement</h3>
                <p class="sikds-doc-history-sub">Tous les téléchargements de ce document</p>
            </div>
            <div class="sikds-doc-history-list">
                @foreach ($document['download_history'] as $download)
                    <div class="sikds-doc-history-row">
                        <div class="sikds-doc-history-row-main">
                            <div class="sikds-doc-history-icon-wrap sikds-doc-history-icon-wrap--download">
                                <i class="fa-solid fa-download"></i>
                            </div>
                            <div class="sikds-doc-history-content">
                                <p class="sikds-doc-history-item-title">{{ $download['title'] }}</p>
                                <p class="sikds-doc-history-item-meta">{{ $download['meta'] }}</p>
                                <p class="sikds-doc-history-item-desc">UUID Filigrane : {{ $download['uuid'] }}</p>
                            </div>
                        </div>
                        <div class="sikds-doc-history-tools">
                            <button type="button" class="sikds-doc-tool-btn" aria-label="Consulter téléchargement">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                            <button type="button" class="sikds-doc-tool-btn" aria-label="Télécharger copie">
                                <i class="fa-solid fa-download"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'activity'" class="sikds-doc-content">
        <div class="sikds-doc-history-card">
            <div class="sikds-doc-history-head">
                <h3 class="sikds-doc-history-title">Journal d'Activité</h3>
                <p class="sikds-doc-history-sub">Historique des actions sur ce document</p>
            </div>
            <div class="sikds-doc-history-list">
                @foreach ($document['activities'] as $activity)
                    <div class="sikds-doc-history-row sikds-doc-history-row--compact">
                        <div class="sikds-doc-history-row-main">
                            <div class="sikds-doc-event-icon {{ $activity['icon_class'] }}">
                                <i class="{{ $activity['icon'] }}"></i>
                            </div>
                            <div class="sikds-doc-history-content">
                                <p class="sikds-doc-history-item-title">{{ $activity['title'] }}</p>
                                <p class="sikds-doc-history-item-meta">{{ $activity['meta'] }}</p>
                                <p class="sikds-doc-history-item-desc">{{ $activity['timestamp'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Confirmation modal: Archive --}}
    <div x-show="modal === 'archive'" x-transition.opacity class="sikds-doc-modal-overlay" @click.self="modal = null" x-cloak>
        <div class="sikds-doc-modal">
            <button type="button" class="sikds-doc-modal-close" @click="modal = null" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-doc-modal-head">
                <div class="sikds-doc-modal-icon sikds-doc-modal-icon--archive">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <h3 class="sikds-doc-modal-title">Archiver le Document</h3>
                    <p class="sikds-doc-modal-subtitle">Confirmer l'archivage</p>
                </div>
            </div>

            <p class="sikds-doc-modal-text">
                Êtes-vous sûr de vouloir archiver le document
                <strong>"{{ $document['title'] }}"</strong> ?
            </p>

            <div class="sikds-doc-modal-actions">
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--cancel" @click="modal = null">
                    Annuler
                </button>
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--archive">
                    <i class="fa-solid fa-box-archive"></i>
                    Archiver
                </button>
            </div>
        </div>
    </div>

    {{-- Confirmation modal: Delete --}}
    <div x-show="modal === 'delete'" x-transition.opacity class="sikds-doc-modal-overlay" @click.self="modal = null" x-cloak>
        <div class="sikds-doc-modal">
            <button type="button" class="sikds-doc-modal-close" @click="modal = null" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-doc-modal-head">
                <div class="sikds-doc-modal-icon sikds-doc-modal-icon--delete">
                    <i class="fa-regular fa-circle-exclamation"></i>
                </div>
                <div>
                    <h3 class="sikds-doc-modal-title">Supprimer le Document</h3>
                    <p class="sikds-doc-modal-subtitle">Action irréversible</p>
                </div>
            </div>

            <p class="sikds-doc-modal-text">
                Êtes-vous sûr de vouloir supprimer définitivement le document
                <strong>"{{ $document['title'] }}"</strong> ?
            </p>

            <div class="sikds-doc-modal-actions">
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--cancel" @click="modal = null">
                    Annuler
                </button>
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--delete">
                    <i class="fa-regular fa-trash-can"></i>
                    Supprimer
                </button>
            </div>
        </div>
    </div>

</div>

@endsection
