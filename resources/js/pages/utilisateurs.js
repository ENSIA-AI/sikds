/**
 * Utilisateurs: search, filters, create/edit modals (JSON API), table refresh.
 */

function readCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function readApiBase() {
    const root = document.querySelector('[data-users-api-base]');
    return root?.dataset?.usersApiBase ?? '/users';
}

function readRolesBootstrap() {
    const el = document.getElementById('users-roles-bootstrap');
    if (!el?.textContent) {
        return [];
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return [];
    }
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

function formatFrTs(iso) {
    if (!iso) {
        return '—';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return '—';
    }
    return d.toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function checkIconSvg() {
    return `<svg class="size-4 shrink-0 text-[#193CB8]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
}

function updateRolePreview(role, titleEl, listEl) {
    if (!titleEl || !listEl) {
        return;
    }
    if (!role) {
        titleEl.textContent = 'Permissions pour le rôle';
        listEl.innerHTML = '';
        return;
    }
    titleEl.textContent = `Permissions pour ${role.name}`;
    listEl.innerHTML = (role.permissions ?? [])
        .map(
            (p) =>
                `<li class="flex items-center gap-2"><span class="shrink-0">${checkIconSvg()}</span><span>${escapeHtml(p.name)}</span></li>`
        )
        .join('');
}

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function wirePermissionAccordions(scope) {
    if (!scope) {
        return;
    }
    scope.querySelectorAll('[data-perm-category]').forEach((cat) => {
        const btn = cat.querySelector('[data-perm-category-toggle]');
        const panel = cat.querySelector('[data-perm-category-panel]');
        const chev = cat.querySelector('[data-perm-chevron]');
        if (!btn || !panel) {
            return;
        }
        btn.addEventListener('click', () => {
            const open = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (chev) {
                chev.classList.toggle('rotate-180', open);
            }
        });
    });
}

function setSelectedRoleInGrid(grid, selectedId, previewTitle, previewList, rolesData) {
    if (!grid) {
        return null;
    }
    const cards = grid.querySelectorAll('[data-role-card]');
    cards.forEach((c) => {
        const id = Number(c.dataset.roleId);
        const isSel = id === selectedId;
        c.dataset.selected = isSel ? 'true' : 'false';
    });
    const role = rolesData.find((r) => Number(r.id) === Number(selectedId)) ?? null;
    updateRolePreview(role, previewTitle, previewList);
    const wrap = previewTitle?.closest('[data-create-role-preview], [data-edit-role-preview]');
    if (wrap) {
        wrap.hidden = !role;
    }
    return role;
}

function collectCheckedPermissionIds(modal, rootKey) {
    const root = modal.querySelector(`[data-perm-root="${rootKey}"]`);
    if (!root) {
        return [];
    }
    return [...root.querySelectorAll('[data-perm-checkbox]:checked')].map((cb) => Number(cb.value));
}

function setCheckedPermissions(modal, rootKey, ids) {
    const root = modal.querySelector(`[data-perm-root="${rootKey}"]`);
    if (!root) {
        return;
    }
    const set = new Set(ids.map(Number));
    root.querySelectorAll('[data-perm-checkbox]').forEach((cb) => {
        cb.checked = set.has(Number(cb.value));
    });
}

function resetCreateForm(modal, rolesData) {
    const form = modal.querySelector('[data-create-user-form]');
    form?.reset();
    const grid = modal.querySelector('[data-create-role-grid]');
    const pTitle = modal.querySelector('[data-create-role-preview-title]');
    const pList = modal.querySelector('[data-create-role-preview-list]');
    const preview = modal.querySelector('[data-create-role-preview]');
    grid?.querySelectorAll('[data-role-card]').forEach((c) => {
        delete c.dataset.selected;
    });
    if (preview) {
        preview.hidden = true;
    }
    updateRolePreview(null, pTitle, pList);
    setCheckedPermissions(modal, 'create', []);
    modal.querySelector('[data-create-user-errors]')?.classList.add('hidden');
}

function buildUserRow(payload) {
    const active = payload.is_active;
    const ts = active ? payload.last_login_at || payload.created_at : payload.last_login_at || payload.updated_at;
    const tsLabel = formatFrTs(ts);
    const statusPrimary = active
        ? `<p class="font-inter text-sm font-medium text-[#008236]">Actif</p><p class="font-inter text-xs text-[#717182]">depuis ${escapeHtml(tsLabel)}</p>`
        : `<p class="font-inter text-sm font-medium text-[#B91C1C]">Inactif</p><p class="font-inter text-xs text-[#717182]">dernière activité ${escapeHtml(tsLabel)}</p>`;
    const roleName = payload.roles?.[0]?.name ?? '—';
    const instName = payload.institution?.name ?? '—';
    const hasEdit = document.querySelector('[data-open-edit-user]') !== null;
    const actionsCell = hasEdit
        ? `<td class="px-4 align-middle text-right">
            <button type="button" data-open-edit-user class="inline-flex size-10 items-center justify-center rounded-[10px] text-[#0A0A0A] transition hover:bg-[#F4F4F5] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]" aria-label="Modifier le rôle">
              <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="6" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="18" cy="12" r="1.6"/></svg>
            </button>
          </td>`
        : `<td class="px-4 align-middle text-right"></td>`;

    const payloadAttr = JSON.stringify(payload).replace(/"/g, '&quot;');

    return `<tr data-user-row data-user-id="${payload.id}" data-user-payload="${payloadAttr}" class="h-[108px] border-b border-black/10 bg-white last:border-b-0">
      <td class="px-4 align-middle"><p class="font-inter text-base font-medium text-[#0A0A0A]">${escapeHtml(payload.full_name)}</p></td>
      <td class="px-4 align-middle">
        <div class="flex items-center gap-2">
          <span class="text-[#717182]" aria-hidden="true"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none"><path d="M4 6.5 12 12l8-5.5M4 18V6.5M20 18V6.5M4 18h16" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>
          <span class="break-all font-inter text-sm text-[#0A0A0A]">${escapeHtml(payload.email)}</span>
        </div>
      </td>
      <td class="px-4 align-middle"><span class="font-inter text-sm text-[#0A0A0A]">${escapeHtml(roleName)}</span></td>
      <td class="px-4 align-middle">
        <div class="flex items-center gap-2">
          <span class="text-[#717182]" aria-hidden="true"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>
          <span class="font-inter text-sm text-[#0A0A0A]">${escapeHtml(instName)}</span>
        </div>
      </td>
      <td class="px-4 align-middle">${statusPrimary}</td>
      ${actionsCell}
    </tr>`;
}

function replaceOrAppendUserRow(tbody, payload) {
    if (!tbody) {
        return;
    }
    const empty = tbody.querySelector('td[colspan="6"]');
    if (empty) {
        empty.closest('tr')?.remove();
    }
    const existing = tbody.querySelector(`[data-user-row][data-user-id="${payload.id}"]`);
    const html = buildUserRow(payload);
    if (existing) {
        existing.outerHTML = html;
    } else {
        tbody.insertAdjacentHTML('beforeend', html);
    }
}

function bumpStat(key, delta) {
    const el = document.querySelector(`[data-stat="${key}"]`);
    if (!el) {
        return;
    }
    const n = Number(el.textContent) || 0;
    el.textContent = String(Math.max(0, n + delta));
}

function parseJsonErrors(data) {
    if (data.errors) {
        return Object.values(data.errors)
            .flat()
            .join(' ');
    }
    return data.message ?? 'Une erreur est survenue.';
}

function wireSearchInput() {
    const input = document.querySelector('[data-users-search]');
    if (!input) {
        return;
    }
    let t;
    input.addEventListener('input', () => {
        window.clearTimeout(t);
        t = window.setTimeout(() => {
            const u = new URL(window.location.href);
            const q = input.value.trim();
            if (q) {
                u.searchParams.set('search', q);
            } else {
                u.searchParams.delete('search');
            }
            u.searchParams.delete('page');
            window.location.href = u.toString();
        }, 400);
    });
}

function wireFilterPopup() {
    const toggle = document.querySelector('[data-filter-toggle]');
    const popup = document.querySelector('[data-filter-popup]');
    if (!toggle || !popup) {
        return;
    }
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = popup.hasAttribute('hidden');
        if (open) {
            popup.removeAttribute('hidden');
            toggle.dataset.open = 'true';
        } else {
            popup.setAttribute('hidden', '');
            delete toggle.dataset.open;
        }
    });
    document.addEventListener('click', () => {
        popup.setAttribute('hidden', '');
        delete toggle.dataset.open;
    });
    popup.addEventListener('click', (e) => e.stopPropagation());
}

function wireCreateUserModal(rolesData) {
    const modal = document.getElementById('create-user-modal');
    if (!modal) {
        return;
    }
    const apiBase = readApiBase();
    let selectedRoleId = null;

    const grid = modal.querySelector('[data-create-role-grid]');
    const pTitle = modal.querySelector('[data-create-role-preview-title]');
    const pList = modal.querySelector('[data-create-role-preview-list]');

    wirePermissionAccordions(modal);

    grid?.addEventListener('click', (e) => {
        const card = e.target.closest('[data-role-card]');
        if (!card || !grid.contains(card)) {
            return;
        }
        const id = Number(card.dataset.roleId);
        selectedRoleId = Number.isFinite(id) ? id : null;
        setSelectedRoleInGrid(grid, selectedRoleId, pTitle, pList, rolesData);
    });

    function open() {
        modal.hidden = false;
        setBodyModalOpen(true);
        modal.querySelector('[data-create-user-panel]')?.focus();
    }

    function close() {
        modal.hidden = true;
        setBodyModalOpen(false);
        selectedRoleId = null;
        resetCreateForm(modal, rolesData);
    }

    document.querySelector('[data-open-create-user]')?.addEventListener('click', open);
    modal.querySelector('[data-close-create-user]')?.addEventListener('click', close);
    modal.querySelector('[data-create-user-overlay]')?.addEventListener('click', close);

    modal.querySelector('[data-create-user-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const errBox = modal.querySelector('[data-create-user-errors]');
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }
        if (!selectedRoleId) {
            if (errBox) {
                errBox.textContent = 'Veuillez sélectionner un rôle.';
                errBox.classList.remove('hidden');
            }
            return;
        }
        const form = e.target;
        const body = {
            full_name: form.full_name.value.trim(),
            email: form.email.value.trim(),
            institution_id: Number(form.institution_id.value),
            role_ids: [selectedRoleId],
            permission_ids: collectCheckedPermissionIds(modal, 'create'),
            auth_type: 'sso',
        };
        try {
            const res = await fetch(apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': readCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (errBox) {
                    errBox.textContent = parseJsonErrors(data);
                    errBox.classList.remove('hidden');
                }
                return;
            }
            showToast(data.message ?? 'Utilisateur créé.');
            const tbody = document.querySelector('[data-users-tbody]');
            replaceOrAppendUserRow(tbody, data.user);
            bumpStat('total', 1);
            if (data.user?.is_active) {
                bumpStat('active', 1);
            } else {
                bumpStat('inactive', 1);
            }
            close();
        } catch {
            showToast('Erreur réseau.', 'error');
        }
    });
}

function wireEditUserModal(rolesData) {
    const modal = document.getElementById('edit-user-modal');
    if (!modal) {
        return;
    }
    const apiBase = readApiBase();
    let selectedRoleId = null;
    let currentUserId = null;

    const grid = modal.querySelector('[data-edit-role-grid]');
    const pTitle = modal.querySelector('[data-edit-role-preview-title]');
    const pList = modal.querySelector('[data-edit-role-preview-list]');
    const nameEl = modal.querySelector('[data-edit-user-name]');
    const idInput = modal.querySelector('[data-edit-user-id]');

    wirePermissionAccordions(modal);

    grid?.addEventListener('click', (e) => {
        const card = e.target.closest('[data-role-card]');
        if (!card || !grid.contains(card)) {
            return;
        }
        const id = Number(card.dataset.roleId);
        selectedRoleId = Number.isFinite(id) ? id : null;
        setSelectedRoleInGrid(grid, selectedRoleId, pTitle, pList, rolesData);
    });

    function openForRow(tr) {
        let payload;
        try {
            payload = JSON.parse(tr.dataset.userPayload ?? '{}');
        } catch {
            return;
        }
        currentUserId = payload.id;
        if (idInput) {
            idInput.value = String(currentUserId);
        }
        if (nameEl) {
            nameEl.textContent = payload.full_name ?? '';
        }
        const roleId = payload.roles?.[0]?.id ?? null;
        selectedRoleId = roleId;
        setSelectedRoleInGrid(grid, roleId, pTitle, pList, rolesData);
        setCheckedPermissions(modal, 'edit', payload.custom_permission_ids ?? []);
        modal.hidden = false;
        setBodyModalOpen(true);
        modal.querySelector('[data-edit-user-panel]')?.focus();
    }

    function close() {
        modal.hidden = true;
        setBodyModalOpen(false);
        selectedRoleId = null;
        currentUserId = null;
        grid?.querySelectorAll('[data-role-card]').forEach((c) => {
            delete c.dataset.selected;
        });
        const preview = modal.querySelector('[data-edit-role-preview]');
        if (preview) {
            preview.hidden = true;
        }
        updateRolePreview(null, pTitle, pList);
        modal.querySelector('[data-edit-user-errors]')?.classList.add('hidden');
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-open-edit-user]');
        if (!btn) {
            return;
        }
        const tr = btn.closest('[data-user-row]');
        if (tr) {
            openForRow(tr);
        }
    });

    modal.querySelector('[data-close-edit-user]')?.addEventListener('click', close);
    modal.querySelector('[data-edit-user-overlay]')?.addEventListener('click', close);

    modal.querySelector('[data-edit-user-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const errBox = modal.querySelector('[data-edit-user-errors]');
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }
        if (!currentUserId || !selectedRoleId) {
            if (errBox) {
                errBox.textContent = 'Veuillez sélectionner un rôle.';
                errBox.classList.remove('hidden');
            }
            return;
        }
        const body = {
            role_ids: [selectedRoleId],
            permission_ids: collectCheckedPermissionIds(modal, 'edit'),
        };
        try {
            const res = await fetch(`${apiBase}/${currentUserId}/permissions`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': readCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (errBox) {
                    errBox.textContent = parseJsonErrors(data);
                    errBox.classList.remove('hidden');
                }
                return;
            }
            showToast(data.message ?? 'Rôle mis à jour.');
            const tbody = document.querySelector('[data-users-tbody]');
            replaceOrAppendUserRow(tbody, data.user);
            close();
        } catch {
            showToast('Erreur réseau.', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const rolesData = readRolesBootstrap();
    wireSearchInput();
    wireFilterPopup();
    wireCreateUserModal(rolesData);
    wireEditUserModal(rolesData);
});
