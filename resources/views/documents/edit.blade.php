@extends('layouts.app')
@section('page_title', __('Gestion des Documents'))
@section('page_subtitle', __('Gérer le cycle de vie des documents institutionnels'))
@section('content')

<div
    class="sikds-doc-edit"
    x-data="documentEditPage({
        document: @js($document),
        availableTags: @js($availableTags),
        institutions: @js($institutions),
        roles: @js($roles),
        targetUsers: @js($targetUsers),
        csrfToken: @js(csrf_token()),
        canPublish: @js($canPublish),
    })"
>
    <div class="sikds-doc-edit-topbar">
        <x-back-link :href="$document['show_url']" :label="__('Retour au document')" class="sikds-doc-back" />
        <div class="sikds-doc-edit-top-actions">
            <button type="button" class="sikds-doc-edit-btn sikds-doc-edit-btn--cancel" @click="showCancelModal = true">
                <i class="fa-solid fa-xmark"></i>
                <span>{{ __('Annuler') }}</span>
            </button>
            <template x-if="canPublish && form.status === 'draft'">
                <button type="button" class="sikds-doc-edit-btn" @click="publish()" :disabled="submitting">
                    <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-bullhorn'"></i>
                    <span>{{ __('Publier') }}</span>
                </button>
            </template>
            <button type="button" class="sikds-doc-edit-btn sikds-doc-edit-btn--save" @click="submit()" :disabled="submitting">
                <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-floppy-disk'"></i>
                <span>{{ __('Enregistrer') }}</span>
            </button>
        </div>
    </div>

    <div x-show="errorList.length" x-cloak class="sikds-toast sikds-toast--danger" role="alert">
        <span class="sikds-toast-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
        <div class="sikds-toast-body">
            <p class="sikds-toast-title">{{ __('Action impossible') }}</p>
            <p class="sikds-toast-message" x-text="errorList[0]"></p>
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

    <div class="sikds-doc-edit-heading">
        <h2 class="sikds-doc-edit-title">{{ __('Modifier le Document') }}</h2>
        <p class="sikds-doc-edit-sub">{{ __('Document ID:') }} {{ $document['id'] }}</p>
    </div>

    <div class="sikds-doc-edit-grid">
        <div class="sikds-doc-edit-left">
            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">{{ __('Informations Générales') }}</h3>

                <label class="sikds-doc-edit-label">
                    <span><i class="fa-regular fa-file-lines"></i> {{ __('Titre du Document') }}</span>
                    <input type="text" class="sikds-doc-edit-input" x-model="form.title">
                </label>

                <label class="sikds-doc-edit-label">
                    <span><i class="fa-regular fa-file-lines"></i> {{ __('Référence') }}</span>
                    <input type="text" class="sikds-doc-edit-input" value="{{ $document['reference'] }}" disabled>
                </label>

                <label class="sikds-doc-edit-label">
                    <span>{{ __('Description') }}</span>
                    <textarea class="sikds-doc-edit-textarea" x-model="form.description"></textarea>
                </label>

                <label class="sikds-doc-edit-label">
                    <span>{{ __('Public Cible') }}</span>
                    <div class="sikds-doc-edit-select-wrap">
                        <select class="sikds-doc-edit-input sikds-doc-edit-select" x-model="form.target_audience">
                            <option value="all">{{ __('Toutes les institutions') }}</option>
                            <option value="specific_institutions">{{ __('Institutions spécifiques') }}</option>
                            <option value="specific_roles">{{ __('Rôles spécifiques') }}</option>
                            <option value="specific_users">{{ __('Utilisateurs spécifiques') }}</option>
                        </select>
                        <i class="fa-solid fa-angle-down"></i>
                    </div>
                </label>

                <div x-show="form.target_audience === 'specific_institutions'" x-cloak class="sikds-doc-edit-target-grid">
                    <template x-for="institution in institutions" :key="institution.id">
                        <label class="sikds-docs-filter-check">
                            <input type="checkbox" :checked="form.target_institution_ids.includes(institution.id)" @change="toggleSelection('target_institution_ids', institution.id)">
                            <span x-text="institution.name"></span>
                        </label>
                    </template>
                </div>

                <div x-show="form.target_audience === 'specific_roles'" x-cloak class="sikds-doc-edit-target-grid">
                    <template x-for="role in roles" :key="role.id">
                        <label class="sikds-docs-filter-check">
                            <input type="checkbox" :checked="form.target_role_ids.includes(role.id)" @change="toggleSelection('target_role_ids', role.id)">
                            <span x-text="role.name"></span>
                        </label>
                    </template>
                </div>

                <div x-show="form.target_audience === 'specific_users'" x-cloak class="sikds-doc-edit-target-grid">
                    <template x-for="targetUser in targetUsers" :key="targetUser.id">
                        <label class="sikds-docs-filter-check">
                            <input type="checkbox" :checked="form.target_user_ids.includes(targetUser.id)" @change="toggleSelection('target_user_ids', targetUser.id)">
                            <span x-text="targetUser.name + (targetUser.email ? ' (' + targetUser.email + ')' : '')"></span>
                        </label>
                    </template>
                </div>
            </section>

            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">{{ __('Dates') }}</h3>
                <div class="sikds-doc-edit-dates-grid">
                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> {{ __("Date d'Émission") }}</span>
                        <input type="date" class="sikds-doc-edit-input" x-model="form.issue_date">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> {{ __("Date d'Effet") }}</span>
                        <input type="date" class="sikds-doc-edit-input" x-model="form.effective_date">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> {{ __("Date d'Expiration") }}</span>
                        <input type="date" class="sikds-doc-edit-input" x-model="form.expiry_date">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span>{{ __('Statut') }}</span>
                        <input type="text" class="sikds-doc-edit-input" :value="statusLabel()" disabled>
                    </label>
                </div>
            </section>
        </div>

        <div class="sikds-doc-edit-right">
            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title sikds-doc-card-heading--with-icon">
                    <i class="fa-solid fa-tags"></i>
                    {{ __('Tags') }}
                </h3>
                <div class="sikds-doc-tags-wrap">
                    <template x-for="tag in availableTags" :key="tag.id">
                        <button
                            type="button"
                            class="sikds-upload-tag-chip sikds-tag"
                            :class="form.tag_ids.includes(tag.id) ? 'sikds-upload-tag-chip--selected' : ''"
                            :style="tag.style"
                            @click="toggleSelection('tag_ids', tag.id)"
                            x-text="tag.label"
                        ></button>
                    </template>
                </div>
            </section>

            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">{{ __('Fichier Actuel') }}</h3>
                <div class="sikds-doc-edit-meta-block">
                    <p class="sikds-doc-meta-label">{{ __('Type de Fichier') }}</p>
                    <p class="sikds-doc-meta-value">{{ $document['file_type'] }}</p>
                </div>
                <div class="sikds-doc-edit-meta-block">
                    <p class="sikds-doc-meta-label">{{ __('Nom du Fichier') }}</p>
                    <p class="sikds-doc-meta-value">{{ $document['file_name'] }}</p>
                </div>
                <div class="sikds-doc-edit-meta-block">
                    <p class="sikds-doc-meta-label">{{ __('Version Actuelle') }}</p>
                    <p class="sikds-doc-meta-value">{{ $document['version'] }}</p>
                </div>
            </section>

            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">{{ __('Nouvelle Version') }}</h3>
                <button type="button" class="sikds-doc-edit-upload-zone" @click="$refs.fileInput.click()">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    <span x-text="newFile ? newFile.name : @js(__('Télécharger une nouvelle version'))"></span>
                    <small>{{ __('PDF (max 50 MB)') }}</small>
                </button>
                <input type="file" class="hidden" x-ref="fileInput" accept=".pdf" @change="pickFile($event)">
                <div class="sikds-doc-edit-info-alert">
                    <i class="fa-solid fa-circle-info"></i>
                    <span x-text="newFile ? @js(__('Une nouvelle version sera créée lors de l’enregistrement.')) : @js(__('Téléversez un nouveau PDF uniquement si vous souhaitez créer une nouvelle version.'))"></span>
                </div>
            </section>
        </div>
    </div>

    <div x-show="showCancelModal" x-transition.opacity class="sikds-doc-modal-overlay" @click.self="showCancelModal = false" x-cloak>
        <div class="sikds-doc-modal">
            <button type="button" class="sikds-doc-modal-close" @click="showCancelModal = false" aria-label="{{ __('Fermer') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-doc-modal-head">
                <div class="sikds-doc-modal-icon sikds-doc-modal-icon--archive">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <h3 class="sikds-doc-modal-title">{{ __('Annuler les Modifications') }}</h3>
                    <p class="sikds-doc-modal-subtitle">{{ __('Modifications non enregistrées') }}</p>
                </div>
            </div>

            <p class="sikds-doc-modal-text">
                {{ __('Êtes-vous sûr de vouloir annuler ? Toutes les modifications non enregistrées seront perdues.') }}
            </p>

            <div class="sikds-doc-modal-actions sikds-doc-modal-actions--cancel-edit">
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--cancel sikds-doc-modal-btn--keep-edit" @click="showCancelModal = false">
                    {{ __('Continuer à modifier') }}
                </button>
                <a href="{{ $document['show_url'] }}" class="sikds-doc-modal-btn sikds-doc-modal-btn--archive sikds-doc-modal-btn--discard">
                    {{ __('Annuler les modifications') }}
                </a>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    function documentEditPage(config) {
        const i18n = {
            serverRedirectFields: @json(__('La requête a été redirigée par le serveur. Vérifiez les champs requis et votre session.')),
            updateFailed: @json(__('La mise à jour a échoué.')),
            updated: @json(__('Document mis à jour.')),
            serverRedirectSession: @json(__('La requête a été redirigée par le serveur. Vérifiez votre session.')),
            publishFailed: @json(__('Publication impossible.')),
            published: @json(__('Document publié.')),
        };
        return {
            showCancelModal: false,
            availableTags: config.availableTags,
            institutions: config.institutions,
            roles: config.roles,
            targetUsers: config.targetUsers,
            canPublish: config.canPublish,
            form: {
                title: config.document.title ?? '',
                description: config.document.description ?? '',
                target_audience: config.document.audience ?? 'all',
                target_institution_ids: [...(config.document.target_institution_ids ?? [])],
                target_role_ids: [...(config.document.target_role_ids ?? [])],
                target_user_ids: [...(config.document.target_user_ids ?? [])],
                tag_ids: [...(config.document.tag_ids ?? [])],
                issue_date: config.document.issue_date ?? '',
                effective_date: config.document.effective_date ?? '',
                expiry_date: config.document.expiry_date ?? '',
                status: config.document.status ?? 'draft',
            },
            newFile: null,
            submitting: false,
            errorList: [],
            successMessage: '',
            toggleSelection(field, id) {
                const list = this.form[field];
                const index = list.indexOf(id);
                if (index >= 0) list.splice(index, 1);
                else list.push(id);
            },
            pickFile(event) {
                this.newFile = event.target.files[0] ?? null;
            },
            statusLabel() {
                return ({
                    active: @js(__('Actif')),
                    draft: @js(__('Brouillon')),
                    archived: @js(__('Archivé')),
                    soft_deleted: @js(__('Supprimé')),
                })[this.form.status] ?? this.form.status;
            },
            async submit() {
                if (this.submitting) return;

                this.submitting = true;
                this.errorList = [];
                this.successMessage = '';

                const formData = new FormData();
                formData.append('title', this.form.title || '');
                formData.append('description', this.form.description || '');
                formData.append('issue_date', this.form.issue_date || '');
                formData.append('effective_date', this.form.effective_date || '');
                formData.append('expiration_date', this.form.expiry_date || '');
                formData.append('target_audience', this.form.target_audience || 'all');
                this.form.tag_ids.forEach((id, idx) => formData.append(`tag_ids[${idx}]`, id));
                this.form.target_institution_ids.forEach((id, idx) => formData.append(`target_institution_ids[${idx}]`, id));
                this.form.target_role_ids.forEach((id, idx) => formData.append(`target_role_ids[${idx}]`, id));
                this.form.target_user_ids.forEach((id, idx) => formData.append(`target_user_ids[${idx}]`, id));
                if (this.newFile) {
                    formData.append('file', this.newFile);
                }

                try {
                    const response = await fetch(config.document.update_url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-HTTP-Method-Override': 'PUT',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.redirected) {
                        throw new Error(i18n.serverRedirectFields);
                    }

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        this.errorList = payload.errors ? Object.values(payload.errors).flat() : [payload.message || i18n.updateFailed];
                        return;
                    }

                    this.successMessage = payload.message || i18n.updated;
                    setTimeout(() => window.location.href = config.document.show_url, 900);
                } catch (error) {
                    this.errorList = [error.message || i18n.updateFailed];
                } finally {
                    this.submitting = false;
                }
            },
            async publish() {
                if (this.submitting) return;

                this.submitting = true;
                this.errorList = [];
                this.successMessage = '';

                try {
                    const response = await fetch(config.document.publish_url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': config.csrfToken,
                        },
                        credentials: 'same-origin',
                    });

                    if (response.redirected) {
                        throw new Error(i18n.serverRedirectSession);
                    }

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        this.errorList = [payload.message || i18n.publishFailed];
                        return;
                    }

                    this.successMessage = payload.message || i18n.published;
                    this.form.status = 'active';
                    setTimeout(() => window.location.href = config.document.show_url, 900);
                } catch (error) {
                    this.errorList = [error.message || i18n.publishFailed];
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>

@endsection
