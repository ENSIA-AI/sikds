<div
    x-data="documentForwardModal({
        searchUrl: @js(route('documents.forward.search-users')),
        csrfToken: @js(csrf_token()),
    })"
    @open-forward-modal.window="openModal($event.detail)"
>
    <div
        x-show="open"
        x-cloak
        class="sikds-doc-modal-overlay"
        @click.self="closeModal()"
        x-transition.opacity
    >
        <div class="sikds-doc-modal" role="dialog" aria-modal="true" aria-labelledby="sikds-forward-title">
            <button type="button" class="sikds-doc-modal-close" @click="closeModal()" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-doc-modal-head">
                <div class="sikds-doc-modal-icon">
                    <i class="fa-solid fa-share-from-square"></i>
                </div>
                <div>
                    <h3 id="sikds-forward-title" class="sikds-doc-modal-title">Transférer le document</h3>
                    <p class="sikds-doc-modal-subtitle">
                        <span x-text="documentReference"></span><span x-show="documentTitle"> — <span x-text="documentTitle"></span></span>
                    </p>
                </div>
            </div>

            <div class="sikds-doc-modal-text" style="padding-top:0.5rem;">
                <label class="sikds-doc-meta-label" for="sikds-forward-search">Destinataire</label>
                <div class="sikds-docs-search" style="display:block;flex:none;min-width:0;width:100%;">
                    <i class="fa-solid fa-magnifying-glass sikds-docs-search-icon"></i>
                    <input
                        id="sikds-forward-search"
                        type="text"
                        autocomplete="off"
                        class="sikds-docs-search-input"
                        placeholder="Rechercher par nom, email ou institution..."
                        x-model="search"
                        @input.debounce.250ms="fetchSuggestions()"
                        @focus="fetchSuggestions()"
                        :disabled="submitting"
                    >

                    <div
                        x-show="suggestionsOpen && (suggestions.length > 0 || loading || (search.length > 0 && !loading))"
                        x-cloak
                        @click.outside="suggestionsOpen = false"
                        style="position:absolute;left:0;right:0;top:calc(100% + 4px);z-index:30;background:#fff;border:1px solid var(--sikds-border-solid);border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,0.08);max-height:260px;overflow-y:auto;"
                    >
                        <template x-if="loading">
                            <div style="padding:0.75rem 1rem;font-size:0.875rem;color:var(--sikds-muted);">Recherche en cours…</div>
                        </template>
                        <template x-if="!loading && suggestions.length === 0 && search.length > 0">
                            <div style="padding:0.75rem 1rem;font-size:0.875rem;color:var(--sikds-muted);">Aucun utilisateur actif ne correspond.</div>
                        </template>
                        <template x-for="user in suggestions" :key="user.id">
                            <button
                                type="button"
                                @click="selectRecipient(user)"
                                style="display:flex;flex-direction:column;align-items:flex-start;gap:0.125rem;width:100%;padding:0.625rem 1rem;text-align:left;background:transparent;border:0;cursor:pointer;border-bottom:1px solid #f1f5f9;"
                                @mouseenter="$el.style.background='var(--sikds-primary-hover-bg)'"
                                @mouseleave="$el.style.background='transparent'"
                            >
                                <span style="font-weight:600;color:var(--sikds-ink);" x-text="user.label"></span>
                                <span style="font-size:0.75rem;color:var(--sikds-muted);" x-text="user.email"></span>
                                <span x-show="user.institution" style="font-size:0.75rem;color:var(--sikds-muted);" x-text="user.institution"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="recipient" x-cloak style="margin-top:0.75rem;padding:0.75rem 1rem;background:var(--sikds-primary-hover-bg);border:1px solid rgba(28,57,142,0.18);border-radius:10px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                        <div style="min-width:0;">
                            <p style="font-weight:600;color:var(--sikds-primary);margin:0;" x-text="recipient?.label"></p>
                            <p style="font-size:0.75rem;color:var(--sikds-primary);margin:0;opacity:0.85;" x-text="recipient?.email"></p>
                            <p x-show="recipient?.institution" style="font-size:0.75rem;color:var(--sikds-primary);margin:0;opacity:0.7;" x-text="recipient?.institution"></p>
                        </div>
                        <button
                            type="button"
                            @click="clearRecipient()"
                            style="background:transparent;border:0;color:var(--sikds-primary);cursor:pointer;font-size:0.875rem;"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <p x-show="errorMessage" x-cloak style="margin-top:0.75rem;color:#b91c1c;font-size:0.875rem;" x-text="errorMessage"></p>
                <p x-show="successMessage" x-cloak style="margin-top:0.75rem;color:#047857;font-size:0.875rem;" x-text="successMessage"></p>
            </div>

            <div class="sikds-doc-modal-actions">
                <button type="button" class="sikds-doc-modal-btn sikds-doc-modal-btn--cancel" @click="closeModal()" :disabled="submitting">
                    Annuler
                </button>
                <button
                    type="button"
                    class="sikds-doc-modal-btn"
                    style="background:var(--sikds-primary);color:#fff;"
                    @mouseenter="$el.style.background='var(--sikds-primary-dark)'"
                    @mouseleave="$el.style.background='var(--sikds-primary)'"
                    :disabled="!recipient || submitting"
                    @click="submit()"
                >
                    <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-paper-plane'"></i>
                    <span x-text="submitting ? 'Envoi…' : 'Transférer'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function documentForwardModal(config) {
        return {
            open: false,
            forwardUrl: '',
            documentReference: '',
            documentTitle: '',
            search: '',
            suggestions: [],
            suggestionsOpen: false,
            loading: false,
            recipient: null,
            submitting: false,
            errorMessage: '',
            successMessage: '',
            _controller: null,
            openModal(detail) {
                this.forwardUrl = detail?.forwardUrl || '';
                this.documentReference = detail?.reference || '';
                this.documentTitle = detail?.title || '';
                this.search = '';
                this.suggestions = [];
                this.recipient = null;
                this.errorMessage = '';
                this.successMessage = '';
                this.suggestionsOpen = false;
                this.open = true;
                this.$nextTick(() => {
                    document.getElementById('sikds-forward-search')?.focus();
                });
            },
            closeModal() {
                if (this.submitting) return;
                this.open = false;
                this.suggestionsOpen = false;
                if (this._controller) this._controller.abort();
            },
            clearRecipient() {
                this.recipient = null;
                this.search = '';
                this.suggestionsOpen = false;
            },
            async fetchSuggestions() {
                this.suggestionsOpen = true;
                this.loading = true;
                this.errorMessage = '';
                if (this._controller) this._controller.abort();
                this._controller = new AbortController();

                try {
                    const url = config.searchUrl + (this.search ? ('?q=' + encodeURIComponent(this.search)) : '');
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        signal: this._controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Recherche indisponible.');
                    }

                    const payload = await response.json();
                    this.suggestions = Array.isArray(payload?.data) ? payload.data : [];
                } catch (error) {
                    if (error?.name === 'AbortError') return;
                    this.errorMessage = error?.message || 'Recherche indisponible.';
                    this.suggestions = [];
                } finally {
                    this.loading = false;
                }
            },
            selectRecipient(user) {
                this.recipient = user;
                this.search = user.label || user.email;
                this.suggestionsOpen = false;
            },
            async submit() {
                if (!this.recipient || !this.forwardUrl || this.submitting) return;
                this.submitting = true;
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const response = await fetch(this.forwardUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': config.csrfToken,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ recipient_id: this.recipient.id }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload?.message || 'Action impossible.');
                    }

                    this.successMessage = payload.message || 'Document transféré avec succès.';
                    this.$dispatch('forward-success', { message: this.successMessage });
                    try {
                        window.sessionStorage.setItem('documents-success-message', this.successMessage);
                    } catch (e) { /* ignore */ }
                    setTimeout(() => {
                        this.open = false;
                        window.location.reload();
                    }, 900);
                } catch (error) {
                    this.errorMessage = error?.message || 'Action impossible.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
