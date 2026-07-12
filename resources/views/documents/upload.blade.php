@extends('layouts.app')
@section('page_title', __('Gestion des Documents'))
@section('page_subtitle', __('Gérer le cycle de vie des documents institutionnels'))
@section('content')

<div
    x-data="uploadPage({
        storeUrl: @js(route('api.documents.create')),
        indexUrl: @js(route('documents.index')),
        csrfToken: @js(csrf_token()),
        availableTags: @js($availableTags),
        institutions: @js($institutions),
        roles: @js($roles),
        targetUsers: @js($targetUsers),
    })"
    x-effect="ensureMetaForIndex(currentFileIdx)"
    class="sikds-upload"
>

    <x-back-link :href="route('documents.index')" :label="__('Retour aux documents')" class="sikds-upload-back" />

    <h2 class="sikds-upload-heading">{{ __('Téléverser des Documents') }}</h2>
    <p class="sikds-upload-sub">{{ __('Ajouter de nouveaux documents au système SIKDS') }}</p>

    <div x-ref="uploadAlerts" class="sikds-upload-page-alerts">
        <div x-show="errorList.length" x-cloak class="sikds-toast sikds-toast--danger" role="alert">
            <span class="sikds-toast-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
            <div class="sikds-toast-body">
                <p class="sikds-toast-title">{{ __('Action impossible') }}</p>
                <template x-for="(msg, idx) in errorList" :key="idx">
                    <p class="sikds-toast-message" x-text="msg"></p>
                </template>
            </div>
            <button type="button" @click="errorList = []" class="sikds-toast-dismiss" aria-label="{{ __('Fermer') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div x-show="successMessage" x-cloak class="sikds-toast sikds-toast--success" role="status">
            <span class="sikds-toast-icon"><i class="fa-solid fa-circle-check"></i></span>
            <div class="sikds-toast-body">
                <p class="sikds-toast-title">{{ __('Opération réussie') }}</p>
                <p class="sikds-toast-message" x-text="successMessage"></p>
            </div>
            <button type="button" @click="successMessage = ''" class="sikds-toast-dismiss" aria-label="{{ __('Fermer') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    {{-- Tab switcher --}}
    <div class="sikds-upload-tabs">
        <button type="button"
            class="sikds-upload-tab"
            :class="mode === 'single' ? 'sikds-upload-tab--active' : 'sikds-upload-tab--idle'"
            @click="setMode('single')"
        >
            <i class="fa-regular fa-file"></i>
            <span>{{ __('Téléversement Simple') }}</span>
        </button>
        <button type="button"
            class="sikds-upload-tab"
            :class="mode === 'batch' ? 'sikds-upload-tab--active' : 'sikds-upload-tab--idle'"
            @click="setMode('batch')"
        >
            <i class="fa-regular fa-copy"></i>
            <span>{{ __("Téléversement en Lot (jusqu'à 5 fichiers)") }}</span>
        </button>
    </div>

    {{-- Top row: PDF drop zone + Tags --}}
    <div class="sikds-upload-top-row">

        {{-- PDF drop zone --}}
        <div class="sikds-upload-card sikds-upload-card--file">
            <div class="sikds-upload-card-header">
                <h3 class="sikds-upload-card-title">
                    <span x-text="mode === 'single' ? @js(__('Fichier PDF')) : @js(__('Fichiers PDF'))"></span>
                </h3>
                <span x-show="mode === 'batch'" class="sikds-upload-file-count" x-text="files.length + @js(__(' / 5 fichiers'))"></span>
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
                        <i :class="mode === 'single' ? 'fa-solid fa-cloud-arrow-up sikds-upload-dropzone-icon' : 'fa-solid fa-layer-group sikds-upload-dropzone-icon'"></i>
                        <p class="sikds-upload-dropzone-label">
                            <span x-text="mode === 'single'
                                ? @js(__('Glissez-déposez votre fichier PDF ici'))
                                : @js(__('Glissez-déposez vos fichiers PDF ici'))"></span>
                        </p>
                        <p class="sikds-upload-dropzone-hint">
                            <span x-text="mode === 'single'
                                ? @js(__('ou cliquez pour parcourir'))
                                : @js(__("ou cliquez pour parcourir (jusqu'à 5 fichiers)"))"></span>
                        </p>
                        <p class="sikds-upload-dropzone-format">
                            <span x-text="mode === 'single'
                                ? @js(__('Format accepté : PDF uniquement'))
                                : @js(__('Format : PDF uniquement • Taille max : 50 MB par fichier'))"></span>
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

            <input id="document-file-input" name="files[]" type="file" x-ref="fileInput" class="hidden" accept=".pdf,application/pdf"
                :multiple="mode === 'batch'"
                @change="handleFileSelect($event)">
        </div>

        {{-- Tags --}}
        <div class="sikds-upload-card sikds-upload-card--tags">
            <h3 class="sikds-upload-card-title">
                <i class="fa-solid fa-tag"></i> {{ __('Tags') }}
            </h3>

            <p class="sikds-upload-tags-label">{{ __('Tags du document courant') }} <span class="sikds-muted-text">{{ __('(au moins un obligatoire)') }}</span></p>
            <div class="sikds-upload-tags-available">
                <template x-for="tag in availableTags" :key="tag.id">
                    <button
                        type="button"
                        class="sikds-upload-tag-chip sikds-tag sikds-tag--table"
                        :class="currentMeta().tag_ids.includes(tag.id) ? 'sikds-upload-tag-chip--selected' : ''"
                        :style="tag.style"
                        @click="toggleCurrentTag(tag.id)"
                        x-text="tag.label"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    {{-- Informations Générales --}}
    <div class="sikds-upload-card">
        <div class="sikds-upload-card-header">
            <h3 class="sikds-upload-card-title">{{ __('Informations Générales') }}</h3>
            <template x-if="mode === 'batch'">
                <div class="sikds-upload-stepper">
                    <button type="button" class="sikds-upload-next-btn" @click="prevStep()" :disabled="!canGoPrev()">
                        <i class="fa-solid fa-chevron-left"></i> {{ __('Précédent') }}
                    </button>
                    <div class="sikds-upload-dots">
                        <template x-for="i in 5" :key="i">
                            <span
                                class="sikds-upload-dot"
                                :class="{
                                    'sikds-upload-dot--active': (i - 1) === currentFileIdx,
                                    'sikds-upload-dot--disabled': (i - 1) >= files.length
                                }"
                                @click="goToStep(i - 1)"
                            ></span>
                        </template>
                    </div>
                    <button type="button" class="sikds-upload-next-btn" @click="nextStep()" :disabled="currentFileIdx >= 4">
                        {{ __('Suivant') }} <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </template>
        </div>

        <template x-if="mode === 'batch'">
            <div class="sikds-upload-current-file">
                <template x-if="files.length > 0">
                    <div class="sikds-upload-current-file-inner">
                        <span class="sikds-upload-current-file-step" x-text="@js(__('Fichier :n', ['n' => 0])).replace(':n', currentFileIdx + 1) + ' / ' + files.length"></span>
                        <span class="sikds-upload-current-file-name" x-text="files[currentFileIdx]?.name"></span>
                    </div>
                </template>
                <template x-if="files.length === 0">
                    <div class="sikds-upload-current-file-empty">
                        {{ __('Ajoutez des fichiers PDF pour commencer la saisie des informations.') }}
                    </div>
                </template>
            </div>
        </template>

        <label class="sikds-upload-label">{{ __('Titre') }} <span class="sikds-upload-required">*</span></label>
        <input
            id="document-title"
            name="document_title"
            type="text"
            class="sikds-upload-input"
            placeholder="{{ __('Ex: Directive MESRS-2024-045') }}"
            x-model="documentsMeta[currentFileIdx].title"
        >

        <label class="sikds-upload-label" style="margin-top: 16px;">{{ __('Description') }}</label>
        <textarea
            id="document-description"
            name="document_description"
            class="sikds-upload-textarea"
            rows="4"
            placeholder="{{ __('Description du document...') }}"
            x-model="documentsMeta[currentFileIdx].description"
        ></textarea>
    </div>

    {{-- Dates --}}
    <div class="sikds-upload-card">
        <h3 class="sikds-upload-card-title">
            <i class="fa-regular fa-calendar"></i> {{ __('Dates') }}
        </h3>
        <div class="sikds-upload-dates-row">
            <div class="sikds-upload-date-field">
                <label class="sikds-upload-label">{{ __("Date d'Émission") }} <span class="sikds-upload-required">*</span></label>
                <input id="document-issue-date" name="document_issue_date" type="date" class="sikds-upload-input" x-model="documentsMeta[currentFileIdx].issue_date">
            </div>
            <div class="sikds-upload-date-field">
                <label class="sikds-upload-label">{{ __("Date d'Effet") }}</label>
                <input id="document-effective-date" name="document_effective_date" type="date" class="sikds-upload-input" x-model="documentsMeta[currentFileIdx].effective_date">
            </div>
            <div class="sikds-upload-date-field">
                <label class="sikds-upload-label">{{ __("Date d'Expiration") }}</label>
                <input id="document-expiration-date" name="document_expiration_date" type="date" class="sikds-upload-input" x-model="documentsMeta[currentFileIdx].expiration_date">
            </div>
        </div>
    </div>

    {{-- Public Cible --}}
    <div class="sikds-upload-card">
        <h3 class="sikds-upload-card-title">
            <i class="fa-solid fa-users"></i> {{ __('Public Cible') }}
        </h3>
        <div class="sikds-upload-audience-options">
            <label class="sikds-upload-radio">
                <input :id="'audience-all-' + currentFileIdx" :name="'audience_' + currentFileIdx" value="all" type="radio" x-model="documentsMeta[currentFileIdx].target_audience">
                <span class="sikds-upload-radio-mark"></span>
                <span class="sikds-upload-radio-content">
                    <span class="sikds-upload-radio-title">{{ __('Toutes les institutions') }}</span>
                    <span class="sikds-upload-radio-desc">{{ __('Tous les utilisateurs du système') }}</span>
                </span>
            </label>
            <label class="sikds-upload-radio">
                <input :id="'audience-institutions-' + currentFileIdx" :name="'audience_' + currentFileIdx" value="specific_institutions" type="radio" x-model="documentsMeta[currentFileIdx].target_audience">
                <span class="sikds-upload-radio-mark"></span>
                <span class="sikds-upload-radio-content">
                    <span class="sikds-upload-radio-title">{{ __('Institutions spécifiques') }}</span>
                    <span class="sikds-upload-radio-desc">{{ __('Sélectionner les institutions') }}</span>
                </span>
            </label>
            <label class="sikds-upload-radio">
                <input :id="'audience-roles-' + currentFileIdx" :name="'audience_' + currentFileIdx" value="specific_roles" type="radio" x-model="documentsMeta[currentFileIdx].target_audience">
                <span class="sikds-upload-radio-mark"></span>
                <span class="sikds-upload-radio-content">
                    <span class="sikds-upload-radio-title">{{ __('Rôles spécifiques') }}</span>
                    <span class="sikds-upload-radio-desc">{{ __('Sélectionner les rôles') }}</span>
                </span>
            </label>
            <label class="sikds-upload-radio">
                <input :id="'audience-users-' + currentFileIdx" :name="'audience_' + currentFileIdx" value="specific_users" type="radio" x-model="documentsMeta[currentFileIdx].target_audience">
                <span class="sikds-upload-radio-mark"></span>
                <span class="sikds-upload-radio-content">
                    <span class="sikds-upload-radio-title">{{ __('Utilisateurs spécifiques') }}</span>
                    <span class="sikds-upload-radio-desc">{{ __('Sélectionner les utilisateurs (prioritaire)') }}</span>
                </span>
            </label>
        </div>

        <div x-show="currentMeta().target_audience === 'specific_institutions'" x-cloak class="sikds-doc-edit-target-grid" style="margin-top: 16px;">
            <template x-for="institution in institutions" :key="institution.id">
                <label class="sikds-docs-filter-check">
                    <input :id="'institution-' + currentFileIdx + '-' + institution.id" :name="'institution_' + currentFileIdx + '[]'" type="checkbox" :checked="currentMeta().target_institution_ids.includes(institution.id)" @change="toggleCurrentSelection('target_institution_ids', institution.id)">
                    <span x-text="institution.name"></span>
                </label>
            </template>
        </div>

        <div x-show="currentMeta().target_audience === 'specific_roles'" x-cloak class="sikds-doc-edit-target-grid" style="margin-top: 16px;">
            <template x-for="role in roles" :key="role.id">
                <label class="sikds-docs-filter-check">
                    <input :id="'role-' + currentFileIdx + '-' + role.id" :name="'role_' + currentFileIdx + '[]'" type="checkbox" :checked="currentMeta().target_role_ids.includes(role.id)" @change="toggleCurrentSelection('target_role_ids', role.id)">
                    <span x-text="role.name"></span>
                </label>
            </template>
        </div>

        <div x-show="currentMeta().target_audience === 'specific_users'" x-cloak class="sikds-doc-edit-target-grid" style="margin-top: 16px;">
            <template x-for="targetUser in targetUsers" :key="targetUser.id">
                <label class="sikds-docs-filter-check">
                    <input :id="'target-user-' + currentFileIdx + '-' + targetUser.id" :name="'target_user_' + currentFileIdx + '[]'" type="checkbox" :checked="currentMeta().target_user_ids.includes(targetUser.id)" @change="toggleCurrentSelection('target_user_ids', targetUser.id)">
                    <span x-text="targetUser.name + (targetUser.email ? ' (' + targetUser.email + ')' : '')"></span>
                </label>
            </template>
        </div>
    </div>

    {{-- Footer actions --}}
    <div class="sikds-upload-footer">
        <a href="{{ route('documents.index') }}" class="sikds-upload-btn-cancel">{{ __('Annuler') }}</a>
        <button type="button" class="sikds-upload-btn-submit" :disabled="submitting || files.length === 0" @click="submit()">
            <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-cloud-arrow-up'"></i>
            <span x-text="mode === 'single'
                ? @js(__('Téléverser le Document'))
                : @js(__('Téléverser')) + ' ' + files.length + ' ' + @js(__('Document(s)') )"></span>
        </button>
    </div>

</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
function uploadPage(config) {
    const i18n = {
        pdfOnly: @json(__('Seuls les fichiers PDF sont autorisés.')),
        addAtLeastOnePdf: @json(__('Ajoutez au moins un fichier PDF avant de continuer.')),
        atLeastOneTag: @json(__('Sélectionnez au moins un tag pour chaque document.')),
        serverRedirectFields: @json(__('La requête a été redirigée par le serveur. Vérifiez les champs requis et votre session.')),
        uploadFailed: @json(__('Le téléversement a échoué.')),
        unexpectedResponse: @json(__('Réponse inattendue du serveur')),
        noDocumentCreated: @json(__('Aucun document créé.')),
        createdSuccess: @json(__('Document(s) créé(s) avec succès.')),
    };
    return {
        availableTags: config.availableTags,
        institutions: config.institutions,
        roles: config.roles,
        targetUsers: config.targetUsers,
        mode: 'single',
        files: [],
        documentsMeta: [],
        dragging: false,
        currentFileIdx: 0,
        submitting: false,
        errorList: [],
        successMessage: '',

        todayIsoDate() {
            const d = new Date();
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        },

        emptyMeta() {
            return {
                title: '',
                description: '',
                issue_date: this.todayIsoDate(),
                effective_date: '',
                expiration_date: '',
                target_audience: 'all',
                target_institution_ids: [],
                target_role_ids: [],
                target_user_ids: [],
                tag_ids: [],
            };
        },

        ensureMetaForIndex(idx) {
            if (!this.documentsMeta[idx]) {
                this.documentsMeta[idx] = this.emptyMeta();
            }
        },

        setMode(nextMode) {
            this.mode = nextMode;
            this.currentFileIdx = 0;
            this.errorList = [];
            this.successMessage = '';
            this.ensureMetaForIndex(0);
            if (nextMode === 'single' && this.files.length > 1) {
                this.files = this.files.slice(0, 1);
                this.documentsMeta = [this.documentsMeta[0] ?? this.emptyMeta()];
            }
        },

        currentMeta() {
            this.ensureMetaForIndex(this.currentFileIdx);
            return this.documentsMeta[this.currentFileIdx];
        },

        toggleCurrentTag(tagId) {
            const meta = this.currentMeta();
            const i = meta.tag_ids.indexOf(tagId);
            if (i >= 0) meta.tag_ids.splice(i, 1);
            else meta.tag_ids.push(tagId);
        },

        toggleCurrentSelection(field, id) {
            const meta = this.currentMeta();
            const list = meta[field];
            const i = list.indexOf(id);
            if (i >= 0) list.splice(i, 1);
            else list.push(id);
        },

        handleDrop(e) {
            this.dragging = false;
            const dropped = Array.from(e.dataTransfer.files).filter(f => this.isPdfFile(f));
            if (dropped.length === 0 && e.dataTransfer.files.length > 0) {
                this.showErrors([i18n.pdfOnly]);
                return;
            }
            this.addFiles(dropped);
        },

        handleFileSelect(e) {
            const selected = Array.from(e.target.files).filter(f => this.isPdfFile(f));
            if (selected.length === 0 && e.target.files.length > 0) {
                this.showErrors([i18n.pdfOnly]);
                e.target.value = '';
                return;
            }
            this.addFiles(selected);
            e.target.value = '';
        },

        addFiles(list) {
            const max = this.mode === 'single' ? 1 : 5;
            this.errorList = [];
            if (this.mode === 'single') {
                this.files = list.slice(0, 1);
                this.documentsMeta[0] = this.documentsMeta[0] ?? this.emptyMeta();
            } else {
                for (const f of list) {
                    if (this.files.length >= max) break;
                    this.files.push(f);
                    this.ensureMetaForIndex(this.files.length - 1);
                }
            }
            const maxIdx = Math.max(0, Math.min(this.files.length, 5) - 1);
            if (this.currentFileIdx > maxIdx) {
                this.currentFileIdx = maxIdx;
            }
        },

        removeFile(idx) {
            this.files.splice(idx, 1);
            this.documentsMeta.splice(idx, 1);
            if (this.currentFileIdx >= this.files.length) {
                this.currentFileIdx = Math.max(0, this.files.length - 1);
            }
            if (this.files.length === 0) {
                this.currentFileIdx = 0;
                this.documentsMeta = [this.emptyMeta()];
            }
        },

        canGoNext() {
            return this.mode === 'batch' && this.currentFileIdx < Math.min(this.files.length, 5) - 1;
        },

        canGoPrev() {
            return this.mode === 'batch' && this.currentFileIdx > 0;
        },

        goToStep(idx) {
            if (idx >= 0 && idx < this.files.length) {
                this.currentFileIdx = idx;
                this.ensureMetaForIndex(idx);
            }
        },

        nextStep() {
            if (this.canGoNext()) {
                this.currentFileIdx += 1;
                this.ensureMetaForIndex(this.currentFileIdx);
            }
        },

        prevStep() {
            if (this.canGoPrev()) {
                this.currentFileIdx -= 1;
                this.ensureMetaForIndex(this.currentFileIdx);
            }
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        isPdfFile(file) {
            const type = (file.type || '').toLowerCase();
            const name = (file.name || '').toLowerCase();

            return type === 'application/pdf' || name.endsWith('.pdf');
        },

        scrollToUploadAlerts() {
            this.$nextTick(() => {
                window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
            });
        },

        showErrors(messages) {
            this.errorList = Array.isArray(messages) ? messages : [messages];
            this.scrollToUploadAlerts();
        },

        async submit() {
            if (this.submitting) return;

            this.errorList = [];
            this.successMessage = '';

            if (this.files.length === 0) {
                this.showErrors([i18n.addAtLeastOnePdf]);
                return;
            }

            for (let idx = 0; idx < this.files.length; idx++) {
                const meta = this.documentsMeta[idx] ?? this.emptyMeta();
                if (!meta.tag_ids || meta.tag_ids.length === 0) {
                    this.showErrors([i18n.atLeastOneTag]);
                    return;
                }
            }

            const formData = new FormData();
            this.files.forEach((file, idx) => {
                formData.append(`files[${idx}]`, file);

                const meta = this.documentsMeta[idx] ?? this.emptyMeta();
                formData.append(`documents_meta[${idx}][title]`, meta.title || '');
                formData.append(`documents_meta[${idx}][description]`, meta.description || '');
                formData.append(`documents_meta[${idx}][issue_date]`, meta.issue_date || '');
                formData.append(`documents_meta[${idx}][effective_date]`, meta.effective_date || '');
                formData.append(`documents_meta[${idx}][expiration_date]`, meta.expiration_date || '');
                formData.append(`documents_meta[${idx}][target_audience]`, meta.target_audience || 'all');

                (meta.tag_ids || []).forEach((id, tagIdx) => {
                    formData.append(`documents_meta[${idx}][tag_ids][${tagIdx}]`, id);
                });
                (meta.target_institution_ids || []).forEach((id, targetIdx) => {
                    formData.append(`documents_meta[${idx}][target_institution_ids][${targetIdx}]`, id);
                });
                (meta.target_role_ids || []).forEach((id, targetIdx) => {
                    formData.append(`documents_meta[${idx}][target_role_ids][${targetIdx}]`, id);
                });
                (meta.target_user_ids || []).forEach((id, targetIdx) => {
                    formData.append(`documents_meta[${idx}][target_user_ids][${targetIdx}]`, id);
                });
            });

            this.submitting = true;

            try {
                const response = await fetch(config.storeUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    credentials: 'same-origin',
                });

                if (response.redirected) {
                    throw new Error(i18n.serverRedirectFields);
                }

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (payload.errors) {
                        this.showErrors(Object.values(payload.errors).flat());
                    } else {
                        this.showErrors([payload.message || i18n.uploadFailed]);
                    }
                    return;
                }

                if (response.status !== 201 || !Array.isArray(payload.documents) || payload.documents.length === 0) {
                    this.showErrors([payload.message || `${i18n.unexpectedResponse} (${response.status}). ${i18n.noDocumentCreated}`]);
                    return;
                }

                this.successMessage = payload.message || i18n.createdSuccess;
                this.scrollToUploadAlerts();
                window.sessionStorage.setItem('documents-success-message', this.successMessage);
                setTimeout(() => window.location.href = config.indexUrl, 900);
            } catch (error) {
                this.showErrors([error.message || i18n.uploadFailed]);
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>

@endsection
