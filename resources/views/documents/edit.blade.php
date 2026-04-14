@extends('layouts.app')
@section('page_title', 'Gestion des Documents')
@section('page_subtitle', 'Gérer le cycle de vie des documents institutionnels')
@section('content')

<div class="sikds-doc-edit" x-data="{ showCancelModal: false }">
    <div class="sikds-doc-edit-topbar">
        <a href="{{ route('documents.show', $document['reference']) }}" class="sikds-doc-back">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Retour au document</span>
        </a>
        <div class="sikds-doc-edit-top-actions">
            <button type="button" class="sikds-doc-edit-btn sikds-doc-edit-btn--cancel" @click="showCancelModal = true">
                <i class="fa-solid fa-xmark"></i>
                <span>Annuler</span>
            </button>
            <button type="button" class="sikds-doc-edit-btn sikds-doc-edit-btn--save">
                <i class="fa-regular fa-floppy-disk"></i>
                <span>Enregistrer</span>
            </button>
        </div>
    </div>

    <div class="sikds-doc-edit-heading">
        <h2 class="sikds-doc-edit-title">Modifier le Document</h2>
        <p class="sikds-doc-edit-sub">Document ID: {{ $document['id'] }}</p>
    </div>

    <div class="sikds-doc-edit-grid">
        <div class="sikds-doc-edit-left">
            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">Informations Générales</h3>

                <label class="sikds-doc-edit-label">
                    <span><i class="fa-regular fa-file-lines"></i> Titre du Document</span>
                    <input type="text" class="sikds-doc-edit-input" value="{{ $document['title'] }}">
                </label>

                <label class="sikds-doc-edit-label">
                    <span><i class="fa-regular fa-file-lines"></i> Référence</span>
                    <input type="text" class="sikds-doc-edit-input" value="{{ $document['reference'] }}">
                </label>

                <label class="sikds-doc-edit-label">
                    <span>Description</span>
                    <textarea class="sikds-doc-edit-textarea">{{ $document['description'] }}</textarea>
                </label>

                <label class="sikds-doc-edit-label">
                    <span>Public Cible</span>
                    <div class="sikds-doc-edit-select-wrap">
                        <select class="sikds-doc-edit-input sikds-doc-edit-select">
                            <option>{{ $document['audience'] }}</option>
                            <option>Toutes les universités</option>
                            <option>Institutions spécifiques</option>
                        </select>
                        <i class="fa-solid fa-angle-down"></i>
                    </div>
                </label>
            </section>

            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">Dates</h3>
                <div class="sikds-doc-edit-dates-grid">
                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> Date d'Émission</span>
                        <input type="text" class="sikds-doc-edit-input" value="{{ $document['edit_issue_date'] }}">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> Date d'Effet</span>
                        <input type="text" class="sikds-doc-edit-input" value="{{ $document['edit_effect_date'] }}">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> Date d'Expiration</span>
                        <input type="text" class="sikds-doc-edit-input" value="{{ $document['edit_expiry_date'] }}">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span>Statut</span>
                        <input type="text" class="sikds-doc-edit-input" value="{{ $document['edit_status'] }}">
                    </label>
                </div>
            </section>
        </div>

        <div class="sikds-doc-edit-right">
            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title sikds-doc-card-heading--with-icon">
                    <i class="fa-solid fa-tags"></i>
                    Tags
                </h3>
                <div class="sikds-doc-tags-wrap">
                    @foreach ($document['tags'] as $tag)
                        <span class="sikds-tag {{ $tag['class'] }}">{{ $tag['label'] }} <i class="fa-solid fa-xmark"></i></span>
                    @endforeach
                </div>
                <div class="sikds-doc-edit-tag-add">
                    <input type="text" class="sikds-doc-edit-input" placeholder="Ajouter un tag">
                    <button type="button" class="sikds-doc-edit-add-btn">Ajouter</button>
                </div>
            </section>

            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">Fichier Actuel</h3>
                <div class="sikds-doc-edit-meta-block">
                    <p class="sikds-doc-meta-label">Type de Fichier</p>
                    <p class="sikds-doc-meta-value">{{ $document['file_type'] }}</p>
                </div>
                <div class="sikds-doc-edit-meta-block">
                    <p class="sikds-doc-meta-label">Nom du Fichier</p>
                    <p class="sikds-doc-meta-value">{{ $document['file_name'] }}</p>
                </div>
                <div class="sikds-doc-edit-meta-block">
                    <p class="sikds-doc-meta-label">Version Actuelle</p>
                    <p class="sikds-doc-meta-value">{{ $document['version'] }}</p>
                </div>
            </section>

            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">Nouvelle Version</h3>
                <button type="button" class="sikds-doc-edit-upload-zone">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    <span>Télécharger une nouvelle version</span>
                    <small>PDF (max 10 MB)</small>
                </button>
                <div class="sikds-doc-edit-info-alert">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Le téléchargement d'une nouvelle version créera la version 2.2</span>
                </div>
            </section>
        </div>
    </div>

    <div x-show="showCancelModal" x-transition.opacity class="sikds-doc-modal-overlay" @click.self="showCancelModal = false" x-cloak>
        <div class="sikds-doc-modal">
            <button type="button" class="sikds-doc-modal-close" @click="showCancelModal = false" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-doc-modal-head">
                <div class="sikds-doc-modal-icon sikds-doc-modal-icon--archive">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <h3 class="sikds-doc-modal-title">Annuler les Modifications</h3>
                    <p class="sikds-doc-modal-subtitle">Modifications non enregistrées</p>
                </div>
            </div>

            <p class="sikds-doc-modal-text">
                Êtes-vous sûr de vouloir annuler ? Toutes les modifications non enregistrées seront perdues.
            </p>

            <div class="sikds-doc-modal-actions sikds-doc-modal-actions--cancel-edit">
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--cancel sikds-doc-modal-btn--keep-edit" @click="showCancelModal = false">
                    Continuer à modifier
                </button>
                <a href="{{ route('documents.show', $document['reference']) }}" class="sikds-doc-modal-btn sikds-doc-modal-btn--archive sikds-doc-modal-btn--discard">
                    Annuler les modifications
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
