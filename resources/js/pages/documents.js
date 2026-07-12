/**
 * Documents list page Alpine component — extracted from
 * resources/views/documents/index.blade.php so the page ships as a Vite module
 * instead of an inline script (CSP hardening).
 *
 * Registered on `window` before Alpine.start() runs (app.js starts Alpine on
 * DOMContentLoaded, after module scripts execute), so the existing
 * x-data="documentListPage({...})" binding keeps working unchanged.
 * Translated strings come from the global window.i18n object (layout).
 */
window.documentListPage = function documentListPage(config) {
    const g = typeof window !== 'undefined' ? window.i18n ?? {} : {};
    const i18n = {
        deleted: g.documentDeleted ?? 'Document supprimé.',
        serverRedirect: g.serverRedirect ?? 'La requête a été redirigée par le serveur. Vérifiez votre session.',
        genericError: g.genericError ?? 'Une erreur est survenue.',
        actionImpossible: g.actionImpossible ?? 'Action impossible.',
    };
    return {
        filtersOpen: false,
        previewOpen: false,
        previewDoc: null,
        modal: null,
        pendingDeleteDoc: null,
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
            if (this.modal) {
                this.modal = null;
                return;
            }
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
        openDeleteModal(doc) {
            this.pendingDeleteDoc = doc;
            this.modal = 'delete';
        },
        async confirmDelete() {
            if (!this.pendingDeleteDoc?.delete_url) return;
            await this.performAction(this.pendingDeleteDoc.delete_url, 'DELETE', i18n.deleted);
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
                    throw new Error(i18n.serverRedirect);
                }

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || i18n.genericError);
                }

                this.modal = null;
                this.pendingDeleteDoc = null;
                this.banner = { message: successMessage, type: 'success' };
                window.location.reload();
            } catch (error) {
                this.banner = { message: error.message || i18n.actionImpossible, type: 'danger' };
            } finally {
                this.loading = false;
            }
        },
    };
};
