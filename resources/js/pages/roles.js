/**
 * Role listing page: create-role modal, JSON submit, append card.
 */
function readCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function setBodyModalOpen(open) {
    document.body.classList.toggle('overflow-hidden', open);
}

function updateSelectedCount(root) {
    const countEl = root.querySelector('[data-selected-count]');
    if (!countEl) {
        return;
    }
    const n = root.querySelectorAll('.perm-checkbox:checked').length;
    const tpl = window.i18n?.permissionsSelectedCount ?? ':count sélectionnées';
    countEl.textContent = tpl.replace(':count', String(n));
}

function wireModal(root) {
    const overlay = root.querySelector('[data-modal-overlay]');
    const panel = root.querySelector('[data-modal-panel]');
    // Trigger + grid live outside the modal root (main page); query document.
    const openBtn = document.querySelector('[data-open-create-role]');
    const closeEls = root.querySelectorAll('[data-close-modal]');
    const form = root.querySelector('[data-create-role-form]');
    const grid = document.querySelector('[data-role-cards-grid]');
    const storeUrl = form?.getAttribute('action') ?? '';
    const errBox = root.querySelector('[data-form-errors]');

    function open() {
        root.hidden = false;
        setBodyModalOpen(true);
        panel?.focus();
    }

    function close() {
        root.hidden = true;
        setBodyModalOpen(false);
        form?.reset();
        root.querySelectorAll('.perm-checkbox').forEach((cb) => {
            cb.checked = false;
        });
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }
        updateSelectedCount(root);
    }

    openBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        open();
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

    root.querySelectorAll('.perm-checkbox').forEach((cb) => {
        cb.addEventListener('change', () => updateSelectedCount(root));
    });

    root.querySelectorAll('[data-select-all-section]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const section = btn.getAttribute('data-select-all-section');
            if (!section) {
                return;
            }
            root.querySelectorAll(`.perm-checkbox[data-perm-section="${section}"]`).forEach((cb) => {
                cb.checked = true;
            });
            updateSelectedCount(root);
        });
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!form || !storeUrl || !grid) {
            return;
        }

        const submitBtn = form.querySelector('[data-submit-role]');
        if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = true;
        }

        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }

        const fd = new FormData(form);
        const permissionIds = fd.getAll('permission_ids[]').map((v) => Number.parseInt(String(v), 10));
        const desc = String(fd.get('description') ?? '').trim();
        const payload = {
            name: String(fd.get('name') ?? '').trim(),
            permission_ids: permissionIds,
        };
        payload.description = desc.length > 0 ? desc : null;

        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': readCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
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
                if (errBox) {
                    errBox.textContent = data.message ?? window.i18n?.genericError ?? 'Une erreur est survenue.';
                    errBox.classList.remove('hidden');
                }
                return;
            }

            if (typeof data.html === 'string' && data.html.length > 0) {
                grid.querySelector('[data-empty-state]')?.remove();
                grid.insertAdjacentHTML('beforeend', data.html.trim());
            }

            close();
        } catch {
            if (errBox) {
                errBox.textContent = window.i18n?.serverUnreachable ?? 'Impossible de contacter le serveur.';
                errBox.classList.remove('hidden');
            }
        } finally {
            if (submitBtn instanceof HTMLButtonElement) {
                submitBtn.disabled = false;
            }
        }
    });

    updateSelectedCount(root);
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('create-role-modal-root');
    if (root) {
        wireModal(root);
    }
});
