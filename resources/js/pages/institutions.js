/**
 * Institutions page: create/edit modal (multipart), grid updates, delete.
 */
function readCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function readApiBase() {
    const fromBody = document.body.dataset.institutionsApiBase;
    const root = document.querySelector('[data-institutions-api-base]');
    const fromRoot = root?.dataset?.institutionsApiBase;
    return fromBody ?? fromRoot ?? '/institutions';
}

function setBodyModalOpen(open) {
    document.body.classList.toggle('overflow-hidden', open);
}

function showToast(message, variant = 'success') {
    const el = document.querySelector('[data-app-toast]');
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.remove('hidden');
    el.classList.toggle('border-emerald-200', variant === 'success');
    el.classList.toggle('bg-emerald-50', variant === 'success');
    el.classList.toggle('text-emerald-900', variant === 'success');
    el.classList.toggle('border-red-200', variant === 'error');
    el.classList.toggle('bg-red-50', variant === 'error');
    el.classList.toggle('text-red-900', variant === 'error');
    window.clearTimeout(showToast._t);
    showToast._t = window.setTimeout(() => {
        el.classList.add('hidden');
    }, 4200);
}

function parseEditPayload(b64) {
    try {
        const json = atob(b64);
        return JSON.parse(json);
    } catch {
        return null;
    }
}

function setMethodSpoof(container, method) {
    if (!container) {
        return;
    }
    container.innerHTML = '';
    if (method === 'PUT') {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_method';
        input.value = 'PUT';
        container.appendChild(input);
    }
}

function wireInstitutionModal(root) {
    const overlay = root.querySelector('[data-modal-overlay]');
    const panel = root.querySelector('[data-modal-panel]');
    const form = root.querySelector('[data-institution-form]');
    const methodSpoof = root.querySelector('[data-method-spoof]');
    const errBox = root.querySelector('[data-form-errors]');
    const titleEl = root.querySelector('[data-modal-title]');
    const subtitleEl = root.querySelector('[data-modal-subtitle]');
    const submitBtn = root.querySelector('[data-submit-institution]');
    const grid = document.querySelector('[data-institutions-grid]');
    const closeEls = root.querySelectorAll('[data-close-modal]');

    const apiBase = readApiBase();
    let mode = 'create';

    function open() {
        root.hidden = false;
        setBodyModalOpen(true);
        panel?.focus();
    }

    function close() {
        root.hidden = true;
        setBodyModalOpen(false);
        form?.reset();
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }
        setMethodSpoof(methodSpoof, null);
        if (form) {
            form.action = `${apiBase}`;
            form.removeAttribute('data-editing-id');
        }
        mode = 'create';
    }

    function openCreate() {
        mode = 'create';
        if (titleEl) {
            titleEl.textContent = 'Nouvelle institution';
        }
        if (subtitleEl) {
            subtitleEl.textContent = "Renseigner les informations de l'institution";
        }
        if (form) {
            form.action = `${apiBase}`;
            form.removeAttribute('data-editing-id');
        }
        setMethodSpoof(methodSpoof, null);
        form?.reset();
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }
        open();
    }

    function openEdit(payload) {
        mode = 'edit';
        if (titleEl) {
            titleEl.textContent = "Modifier l'Institution";
        }
        if (subtitleEl) {
            subtitleEl.textContent = "Modifier les informations de l'institution";
        }
        if (form) {
            form.action = `${apiBase}/${payload.id}`;
            form.setAttribute('data-editing-id', String(payload.id));
        }
        setMethodSpoof(methodSpoof, 'PUT');
        const nameInput = form?.querySelector('[name="name"]');
        const codeInput = form?.querySelector('[name="code"]');
        const emailInput = form?.querySelector('[name="contact_email"]');
        const phoneInput = form?.querySelector('[name="contact_phone"]');
        const addressInput = form?.querySelector('[name="address"]');
        if (nameInput) {
            nameInput.value = payload.name ?? '';
        }
        if (codeInput) {
            codeInput.value = payload.code ?? '';
        }
        if (emailInput) {
            emailInput.value = payload.contact_email ?? '';
        }
        if (phoneInput) {
            phoneInput.value = payload.contact_phone ?? '';
        }
        if (addressInput) {
            addressInput.value = payload.address ?? '';
        }
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }
        open();
    }

    document.addEventListener('click', (e) => {
        const t = e.target;
        if (!(t instanceof Element)) {
            return;
        }
        if (!t.closest('[data-open-create-institution]')) {
            return;
        }
        e.preventDefault();
        openCreate();
    });

    closeEls.forEach((el) => el.addEventListener('click', () => close()));

    overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) {
            close();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !root.hidden) {
            close();
        }
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!form) {
            return;
        }

        if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = true;
        }
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }

        const fd = new FormData(form);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': readCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await res.json().catch(() => ({}));

            if (res.status === 422 && data.errors) {
                const lines = Object.values(data.errors).flat();
                if (errBox) {
                    errBox.textContent = lines.join(' ');
                    errBox.classList.remove('hidden');
                }
                return;
            }

            if (!res.ok) {
                const msg = data.message ?? 'Une erreur est survenue.';
                if (errBox) {
                    errBox.textContent = msg;
                    errBox.classList.remove('hidden');
                } else {
                    showToast(msg, 'error');
                }
                return;
            }

            if (typeof data.message === 'string') {
                showToast(data.message, 'success');
            }

            if (grid && typeof data.html === 'string' && data.html.length > 0) {
                grid.querySelector('[data-institutions-empty]')?.remove();
                if (mode === 'edit' && form.dataset.editingId) {
                    const existing = document.getElementById(`institution-card-${form.dataset.editingId}`);
                    if (existing) {
                        const tpl = document.createElement('template');
                        tpl.innerHTML = data.html.trim();
                        const next = tpl.content.firstElementChild;
                        if (next) {
                            existing.replaceWith(next);
                        }
                    }
                } else {
                    grid.insertAdjacentHTML('beforeend', data.html.trim());
                }
                wireDeleteHandlers();
                wireEditHandlers();
            }

            close();
        } catch {
            if (errBox) {
                errBox.textContent = 'Impossible de contacter le serveur.';
                errBox.classList.remove('hidden');
            } else {
                showToast('Impossible de contacter le serveur.', 'error');
            }
        } finally {
            if (submitBtn instanceof HTMLButtonElement) {
                submitBtn.disabled = false;
            }
        }
    });

    function wireEditHandlers() {
        document.querySelectorAll('[data-open-institution-edit]').forEach((btn) => {
            if (btn.dataset.wired === '1') {
                return;
            }
            btn.dataset.wired = '1';
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const raw = btn.getAttribute('data-edit-payload');
                if (!raw) {
                    return;
                }
                const payload = parseEditPayload(raw);
                if (payload?.id) {
                    openEdit(payload);
                }
            });
        });
    }

    function wireDeleteHandlers() {
        document.querySelectorAll('[data-delete-institution]').forEach((btn) => {
            if (btn.dataset.wired === '1') {
                return;
            }
            btn.dataset.wired = '1';
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-institution-id');
                const name = btn.getAttribute('data-institution-name') ?? '';
                if (!id) {
                    return;
                }
                if (!window.confirm(`Supprimer l'institution « ${name} » ?`)) {
                    return;
                }
                try {
                    const res = await fetch(`${apiBase}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': readCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        showToast(data.message ?? 'Suppression impossible.', 'error');
                        return;
                    }
                    document.getElementById(`institution-card-${id}`)?.remove();
                    if (typeof data.message === 'string') {
                        showToast(data.message, 'success');
                    }
                    if (grid && !grid.querySelector('[data-institution-card]')) {
                        grid.insertAdjacentHTML(
                            'beforeend',
                            `<p data-institutions-empty class="col-span-full w-full rounded-[14px] border border-black/10 bg-white p-6 text-sm text-[#717182] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]">Aucune institution pour le moment.</p>`,
                        );
                    }
                } catch {
                    showToast('Impossible de contacter le serveur.', 'error');
                }
            });
        });
    }

    wireEditHandlers();
    wireDeleteHandlers();
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('institution-modal-root');
    if (root) {
        wireInstitutionModal(root);
    } else {
        document.querySelectorAll('[data-delete-institution]').forEach((btn) => {
            if (btn.dataset.wired === '1') {
                return;
            }
            btn.dataset.wired = '1';
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const apiBase = readApiBase();
                const id = btn.getAttribute('data-institution-id');
                const name = btn.getAttribute('data-institution-name') ?? '';
                if (!id || !window.confirm(`Supprimer l'institution « ${name} » ?`)) {
                    return;
                }
                try {
                    const res = await fetch(`${apiBase}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': readCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        showToast(data.message ?? 'Suppression impossible.', 'error');
                        return;
                    }
                    document.getElementById(`institution-card-${id}`)?.remove();
                    if (typeof data.message === 'string') {
                        showToast(data.message, 'success');
                    }
                } catch {
                    showToast('Impossible de contacter le serveur.', 'error');
                }
            });
        });
    }
});
