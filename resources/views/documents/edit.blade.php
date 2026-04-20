@extends('layouts.app')
@section('page_title', 'Gestion des Documents')
@section('page_subtitle', 'Gérer le cycle de vie des documents institutionnels')
@section('content')

<div
    class="sikds-doc-edit"
    x-data="documentEditPage({
        document: @js($document),
        availableTags: @js($availableTags),
        institutions: @js($institutions),
        roles: @js($roles),
        csrfToken: @js(csrf_token()),
        canPublish: @js($canPublish),
    })"
>
    <div class="sikds-doc-edit-topbar">
        <a href="{{ $document['show_url'] }}" class="sikds-doc-back">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Retour au document</span>
        </a>
        <div class="sikds-doc-edit-top-actions">
            <button type="button" class="sikds-doc-edit-btn sikds-doc-edit-btn--cancel" @click="showCancelModal = true">
                <i class="fa-solid fa-xmark"></i>
                <span>Annuler</span>
            </button>
            <template x-if="canPublish && form.status === 'draft'">
                <button type="button" class="sikds-doc-edit-btn" @click="publish()" :disabled="submitting">
                    <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-bullhorn'"></i>
                    <span>Publier</span>
                </button>
            </template>
            <button type="button" class="sikds-doc-edit-btn sikds-doc-edit-btn--save" @click="submit()" :disabled="submitting">
                <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-floppy-disk'"></i>
                <span>Enregistrer</span>
            </button>
        </div>
    </div>

    <div x-show="errorList.length" x-cloak class="sikds-toast sikds-toast--danger" role="alert">
        <span class="sikds-toast-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
        <div class="sikds-toast-body">
            <p class="sikds-toast-title">Action impossible</p>
            <p class="sikds-toast-message" x-text="errorList[0]"></p>
        </div>
        <button type="button" @click="errorList = []" class="sikds-toast-dismiss" aria-label="Fermer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div x-show="successMessage" x-cloak class="sikds-toast sikds-toast--success" role="status">
        <span class="sikds-toast-icon"><i class="fa-solid fa-circle-check"></i></span>
        <div class="sikds-toast-body">
            <p class="sikds-toast-title">Opération réussie</p>
            <p class="sikds-toast-message" x-text="successMessage"></p>
        </div>
        <button type="button" @click="successMessage = ''" class="sikds-toast-dismiss" aria-label="Fermer">
            <i class="fa-solid fa-xmark"></i>
        </button>
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
                    <input type="text" class="sikds-doc-edit-input" x-model="form.title">
                </label>

                <label class="sikds-doc-edit-label">
                    <span><i class="fa-regular fa-file-lines"></i> Référence</span>
                    <input type="text" class="sikds-doc-edit-input" value="{{ $document['reference'] }}" disabled>
                </label>

                <label class="sikds-doc-edit-label">
                    <span>Description</span>
                    <textarea class="sikds-doc-edit-textarea" x-model="form.description"></textarea>
                </label>

                <label class="sikds-doc-edit-label">
                    <span>Public Cible</span>
                    <div class="sikds-doc-edit-select-wrap">
                        <select class="sikds-doc-edit-input sikds-doc-edit-select" x-model="form.target_audience">
                            <option value="all">Toutes les institutions</option>
                            <option value="specific_institutions">Institutions spécifiques</option>
                            <option value="specific_roles">Rôles spécifiques</option>
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
            </section>

            <section class="sikds-doc-edit-card">
                <h3 class="sikds-doc-edit-card-title">Dates</h3>
                <div class="sikds-doc-edit-dates-grid">
                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> Date d'Émission</span>
                        <input type="date" class="sikds-doc-edit-input" x-model="form.issue_date">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> Date d'Effet</span>
                        <input type="date" class="sikds-doc-edit-input" x-model="form.effective_date">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span><i class="fa-regular fa-calendar"></i> Date d'Expiration</span>
                        <input type="date" class="sikds-doc-edit-input" x-model="form.expiry_date">
                    </label>

                    <label class="sikds-doc-edit-label">
                        <span>Statut</span>
                        <input type="text" class="sikds-doc-edit-input" :value="statusLabel()" disabled>
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
                <button type="button" class="sikds-doc-edit-upload-zone" @click="$refs.fileInput.click()">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    <span x-text="newFile ? newFile.name : 'Télécharger une nouvelle version'"></span>
                    <small>PDF (max 50 MB)</small>
                </button>
                <input type="file" class="hidden" x-ref="fileInput" accept=".pdf" @change="pickFile($event)">
                <div class="sikds-doc-edit-info-alert">
                    <i class="fa-solid fa-circle-info"></i>
                    <span x-text="newFile ? 'Une nouvelle version sera créée lors de l’enregistrement.' : 'Téléversez un nouveau PDF uniquement si vous souhaitez créer une nouvelle version.'"></span>
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
                <a href="{{ $document['show_url'] }}" class="sikds-doc-modal-btn sikds-doc-modal-btn--archive sikds-doc-modal-btn--discard">
                    Annuler les modifications
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function documentEditPage(config) {
        return {
            showCancelModal: false,
            availableTags: config.availableTags,
            institutions: config.institutions,
            roles: config.roles,
            canPublish: config.canPublish,
            form: {
                title: config.document.title ?? '',
                description: config.document.description ?? '',
                target_audience: config.document.audience ?? 'all',
                target_institution_ids: [...(config.document.target_institution_ids ?? [])],
                target_role_ids: [...(config.document.target_role_ids ?? [])],
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
                    active: 'Actif',
                    draft: 'Brouillon',
                    archived: 'Archivé',
                    soft_deleted: 'Supprimé',
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
                        throw new Error('La requête a été redirigée par le serveur. Vérifiez les champs requis et votre session.');
                    }

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        this.errorList = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'La mise à jour a échoué.'];
                        return;
                    }

                    this.successMessage = payload.message || 'Document mis à jour.';
                    setTimeout(() => window.location.href = config.document.show_url, 900);
                } catch (error) {
                    this.errorList = [error.message || 'La mise à jour a échoué.'];
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
                        throw new Error('La requête a été redirigée par le serveur. Vérifiez votre session.');
                    }

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        this.errorList = [payload.message || 'Publication impossible.'];
                        return;
                    }

                    this.successMessage = payload.message || 'Document publié.';
                    this.form.status = 'active';
                    setTimeout(() => window.location.href = config.document.show_url, 900);
                } catch (error) {
                    this.errorList = [error.message || 'Publication impossible.'];
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>

@endsection
