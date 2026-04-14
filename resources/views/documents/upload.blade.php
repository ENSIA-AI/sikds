@extends('layouts.app')
@section('page_title', 'Gestion des Documents')
@section('page_subtitle', 'Gérer le cycle de vie des documents institutionnels')
@section('content')

<div x-data="uploadPage()" class="sikds-upload">

    <a href="{{ route('documents.index') }}" class="sikds-upload-back">
        <i class="fa-solid fa-arrow-left"></i> Retour aux documents
    </a>

    <h2 class="sikds-upload-heading">Téléverser des Documents</h2>
    <p class="sikds-upload-sub">Ajouter de nouveaux documents au système SIKDS</p>

    {{-- Tab switcher --}}
    <div class="sikds-upload-tabs">
        <button type="button"
            class="sikds-upload-tab"
            :class="mode === 'single' ? 'sikds-upload-tab--active' : 'sikds-upload-tab--idle'"
            @click="mode = 'single'"
        >
            <i class="fa-regular fa-file"></i>
            <span>Téléversement Simple</span>
        </button>
        <button type="button"
            class="sikds-upload-tab"
            :class="mode === 'batch' ? 'sikds-upload-tab--active' : 'sikds-upload-tab--idle'"
            @click="mode = 'batch'"
        >
            <i class="fa-regular fa-copy"></i>
            <span>Téléversement en Lot (jusqu'à 5 fichiers)</span>
        </button>
    </div>

    {{-- Top row: PDF drop zone + Tags --}}
    <div class="sikds-upload-top-row">

        {{-- PDF drop zone --}}
        <div class="sikds-upload-card sikds-upload-card--file">
            <div class="sikds-upload-card-header">
                <h3 class="sikds-upload-card-title">
                    <span x-text="mode === 'single' ? 'Fichier PDF' : 'Fichiers PDF'"></span>
                </h3>
                <span x-show="mode === 'batch'" class="sikds-upload-file-count" x-text="files.length + ' / 5 fichiers'"></span>
            </div>

            <div class="sikds-upload-dropzone"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop($event)"
                :class="dragging ? 'sikds-upload-dropzone--hover' : ''"
                @click="$refs.fileInput.click()"
            >
                <template x-if="files.length === 0">
                    <div class="sikds-upload-dropzone-inner">
                        <i class="fa-solid fa-cloud-arrow-up sikds-upload-dropzone-icon"></i>
                        <p class="sikds-upload-dropzone-label">
                            <span x-text="mode === 'single'
                                ? 'Glissez-déposez votre fichier PDF ici'
                                : 'Glissez-déposez vos fichiers PDF ici'"></span>
                        </p>
                        <p class="sikds-upload-dropzone-hint">
                            <span x-text="mode === 'single'
                                ? 'ou cliquez pour parcourir'
                                : 'ou cliquez pour parcourir (jusqu\'à 5 fichiers)'"></span>
                        </p>
                        <p class="sikds-upload-dropzone-format">
                            <span x-text="mode === 'single'
                                ? 'Format accepté : PDF uniquement'
                                : 'Format : PDF uniquement • Taille max : 50 MB par fichier'"></span>
                        </p>
                    </div>
                </template>

                <template x-if="files.length > 0">
                    <div class="sikds-upload-filelist">
                        <template x-for="(file, idx) in files" :key="idx">
                            <div class="sikds-upload-fileitem">
                                <i class="fa-solid fa-file-pdf sikds-upload-fileitem-icon"></i>
                                <span class="sikds-upload-fileitem-name" x-text="file.name"></span>
                                <span class="sikds-upload-fileitem-size" x-text="formatSize(file.size)"></span>
                                <button type="button" class="sikds-upload-fileitem-remove" @click.stop="removeFile(idx)">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <input type="file" x-ref="fileInput" class="hidden" accept=".pdf"
                :multiple="mode === 'batch'"
                @change="handleFileSelect($event)">
        </div>

        {{-- Tags --}}
        <div class="sikds-upload-card sikds-upload-card--tags">
            <h3 class="sikds-upload-card-title">
                <i class="fa-solid fa-tag"></i> Tags
            </h3>

            <p class="sikds-upload-tags-label">Tags disponibles</p>
            <div class="sikds-upload-tags-available">
                @foreach ($availableTags as $tag)
                    <button type="button"
                        class="sikds-upload-tag-chip"
                        :class="selectedTags.includes('{{ $tag }}') ? 'sikds-upload-tag-chip--selected' : ''"
                        @click="toggleTag('{{ $tag }}')"
                    >{{ $tag }}</button>
                @endforeach
            </div>

            <p class="sikds-upload-tags-label" style="margin-top: 16px;">Ajouter un tag personnalisé</p>
            <div class="sikds-upload-tags-custom">
                <input type="text" placeholder="Nouveau tag..." class="sikds-upload-tags-input"
                    x-model="customTag"
                    @keydown.enter.prevent="addCustomTag()">
                <button type="button" class="sikds-upload-tags-add" @click="addCustomTag()">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Informations Générales --}}
    <div class="sikds-upload-card">
        <div class="sikds-upload-card-header">
            <h3 class="sikds-upload-card-title">Informations Générales</h3>
            <template x-if="mode === 'batch' && files.length > 1">
                <div class="sikds-upload-dots">
                    <template x-for="(f, i) in files" :key="i">
                        <span class="sikds-upload-dot" :class="i === currentFileIdx ? 'sikds-upload-dot--active' : ''" @click="currentFileIdx = i"></span>
                    </template>
                </div>
            </template>
        </div>

        <label class="sikds-upload-label">Titre <span class="sikds-upload-required">*</span></label>
        <input type="text" class="sikds-upload-input" placeholder="Ex: Directive MESRS-2024-045">

        <label class="sikds-upload-label" style="margin-top: 16px;">Description</label>
        <textarea class="sikds-upload-textarea" rows="4" placeholder="Description du document..."></textarea>
    </div>

    {{-- Dates --}}
    <div class="sikds-upload-card">
        <h3 class="sikds-upload-card-title">
            <i class="fa-regular fa-calendar"></i> Dates
        </h3>
        <div class="sikds-upload-dates-row">
            <div class="sikds-upload-date-field">
                <label class="sikds-upload-label">Date d'Émission <span class="sikds-upload-required">*</span></label>
                <input type="date" class="sikds-upload-input" value="2026-01-23">
            </div>
            <div class="sikds-upload-date-field">
                <label class="sikds-upload-label">Date d'Effet</label>
                <input type="date" class="sikds-upload-input" value="2026-01-23">
            </div>
            <div class="sikds-upload-date-field">
                <label class="sikds-upload-label">Date d'Expiration</label>
                <input type="date" class="sikds-upload-input" value="2026-01-23">
            </div>
        </div>
    </div>

    {{-- Public Cible --}}
    <div class="sikds-upload-card">
        <h3 class="sikds-upload-card-title">
            <i class="fa-solid fa-users"></i> Public Cible
        </h3>
        <div class="sikds-upload-audience-options">
            <label class="sikds-upload-radio">
                <input type="radio" name="audience" value="all" x-model="audience">
                <span class="sikds-upload-radio-mark"></span>
                <span class="sikds-upload-radio-content">
                    <span class="sikds-upload-radio-title">Toutes les institutions</span>
                    <span class="sikds-upload-radio-desc">Tous les utilisateurs du système</span>
                </span>
            </label>
            <label class="sikds-upload-radio">
                <input type="radio" name="audience" value="institutions" x-model="audience">
                <span class="sikds-upload-radio-mark"></span>
                <span class="sikds-upload-radio-content">
                    <span class="sikds-upload-radio-title">Institutions spécifiques</span>
                    <span class="sikds-upload-radio-desc">Sélectionner les institutions</span>
                </span>
            </label>
            <label class="sikds-upload-radio">
                <input type="radio" name="audience" value="roles" x-model="audience">
                <span class="sikds-upload-radio-mark"></span>
                <span class="sikds-upload-radio-content">
                    <span class="sikds-upload-radio-title">Rôles spécifiques</span>
                    <span class="sikds-upload-radio-desc">Sélectionner les rôles</span>
                </span>
            </label>
        </div>
    </div>

    {{-- Footer actions --}}
    <div class="sikds-upload-footer">
        <a href="{{ route('documents.index') }}" class="sikds-upload-btn-cancel">Annuler</a>
        <button type="button" class="sikds-upload-btn-submit">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span x-text="mode === 'single'
                ? 'Téléverser le Document'
                : 'Téléverser ' + files.length + ' Document(s)'"></span>
        </button>
    </div>

</div>

<a href="#" class="sikds-fab" aria-label="Notifications">
    <span class="relative inline-flex">
        <img src="/bell.svg" alt="" class="h-5 w-5">
        <span class="sikds-fab-dot" aria-hidden="true"></span>
    </span>
</a>

<script>
function uploadPage() {
    return {
        mode: 'single',
        files: [],
        dragging: false,
        selectedTags: [],
        customTag: '',
        audience: '',
        currentFileIdx: 0,

        toggleTag(tag) {
            const i = this.selectedTags.indexOf(tag);
            if (i >= 0) this.selectedTags.splice(i, 1);
            else this.selectedTags.push(tag);
        },

        addCustomTag() {
            const t = this.customTag.trim();
            if (t && !this.selectedTags.includes(t)) {
                this.selectedTags.push(t);
            }
            this.customTag = '';
        },

        handleDrop(e) {
            this.dragging = false;
            const dropped = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf');
            this.addFiles(dropped);
        },

        handleFileSelect(e) {
            const selected = Array.from(e.target.files).filter(f => f.type === 'application/pdf');
            this.addFiles(selected);
            e.target.value = '';
        },

        addFiles(list) {
            const max = this.mode === 'single' ? 1 : 5;
            if (this.mode === 'single') {
                this.files = list.slice(0, 1);
            } else {
                for (const f of list) {
                    if (this.files.length >= max) break;
                    this.files.push(f);
                }
            }
        },

        removeFile(idx) {
            this.files.splice(idx, 1);
            if (this.currentFileIdx >= this.files.length) {
                this.currentFileIdx = Math.max(0, this.files.length - 1);
            }
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }
    };
}
</script>

@endsection
