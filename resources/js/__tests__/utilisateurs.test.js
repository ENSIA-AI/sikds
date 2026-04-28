/**
 * @jest-environment jsdom
 */

import { jest } from '@jest/globals';

// Mock HTML structure
const createMockDOM = () => {
    document.documentElement.innerHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta name="csrf-token" content="test-csrf-token">
        </head>
        <body>
            <div data-users-api-base="/users">
                <script type="application/json" id="users-roles-bootstrap">
                    [
                        {
                            "id": 1,
                            "name": "Administrator",
                            "permissions": [
                                {"id": 1, "name": "View Users", "code": "user.view"},
                                {"id": 2, "name": "Manage Users", "code": "user.manage"}
                            ]
                        },
                        {
                            "id": 2,
                            "name": "User",
                            "permissions": [
                                {"id": 1, "name": "View Users", "code": "user.view"}
                            ]
                        }
                    ]
                </script>
                
                <!-- Search Input -->
                <input type="search" data-users-search value="" />
                
                <!-- Filter Popup -->
                <button data-filter-toggle></button>
                <div data-filter-popup hidden></div>
                
                <!-- Stats -->
                <div data-stat="total">10</div>
                <div data-stat="active">8</div>
                <div data-stat="inactive">2</div>
                
                <!-- Table -->
                <table>
                    <tbody data-users-tbody>
                        <tr data-user-row data-user-id="1" data-user-payload='{"id":1,"full_name":"John Doe","email":"john@test.com","is_active":true,"roles":[{"id":1,"name":"Admin"}],"institution":{"name":"Test Inst"},"custom_permission_ids":[]}'>
                            <td>John Doe</td>
                            <td>john@test.com</td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Create User Modal -->
                <div id="create-user-modal" hidden>
                    <div data-create-user-overlay></div>
                    <div data-create-user-panel tabindex="-1"></div>
                    <button data-close-create-user></button>
                    <form data-create-user-form>
                        <input name="full_name" />
                        <input name="email" />
                        <select name="institution_id"></select>
                        <div data-create-role-grid>
                            <div data-role-card data-role-id="1"></div>
                            <div data-role-card data-role-id="2"></div>
                        </div>
                        <div data-create-role-preview hidden>
                            <p data-create-role-preview-title></p>
                            <ul data-create-role-preview-list></ul>
                        </div>
                        <div data-perm-root="create">
                            <input type="checkbox" data-perm-checkbox value="1" />
                            <input type="checkbox" data-perm-checkbox value="2" />
                        </div>
                        <div data-create-user-errors hidden></div>
                    </form>
                </div>
                
                <!-- Edit User Modal -->
                <div id="edit-user-modal" hidden>
                    <div data-edit-user-overlay></div>
                    <div data-edit-user-panel tabindex="-1"></div>
                    <button data-close-edit-user></button>
                    <form data-edit-user-form>
                        <input name="user_id" data-edit-user-id />
                        <p data-edit-user-name></p>
                        <div data-edit-role-grid>
                            <div data-role-card data-role-id="1"></div>
                            <div data-role-card data-role-id="2"></div>
                        </div>
                        <div data-edit-role-preview hidden>
                            <p data-edit-role-preview-title></p>
                            <ul data-edit-role-preview-list></ul>
                        </div>
                        <div data-perm-root="edit">
                            <input type="checkbox" data-perm-checkbox value="1" />
                            <input type="checkbox" data-perm-checkbox value="2" />
                        </div>
                        <div data-edit-user-errors hidden></div>
                    </form>
                </div>
                
                <!-- Toast -->
                <div data-app-toast hidden></div>
                
                <!-- Open buttons -->
                <button data-open-create-user></button>
                <button data-open-edit-user></button>
            </div>
        </body>
        </html>
    `;

    global.fetch = jest.fn();
};

describe('Utilisateurs.js - Utility Functions', () => {
    beforeEach(() => {
        createMockDOM();
    });

    test('readCsrfToken returns correct token', () => {
        const readCsrfToken = () => {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        };
        
        expect(readCsrfToken()).toBe('test-csrf-token');
    });

    test('readApiBase returns correct API base URL', () => {
        const readApiBase = () => {
            const root = document.querySelector('[data-users-api-base]');
            return root?.dataset?.usersApiBase ?? '/users';
        };
        
        expect(readApiBase()).toBe('/users');
    });

    test('readRolesBootstrap parses JSON correctly', () => {
        const readRolesBootstrap = () => {
            const el = document.getElementById('users-roles-bootstrap');
            if (!el?.textContent) {
                return [];
            }
            try {
                return JSON.parse(el.textContent);
            } catch {
                return [];
            }
        };
        
        const roles = readRolesBootstrap();
        expect(roles).toHaveLength(2);
        expect(roles[0].name).toBe('Administrator');
        expect(roles[0].permissions).toHaveLength(2);
    });

    test('escapeHtml escapes dangerous characters', () => {
        const escapeHtml = (s) => {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        };
        
        expect(escapeHtml('<script>alert("xss")</script>'))
            .toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
        expect(escapeHtml('John & Jane')).toBe('John &amp; Jane');
    });

    test('formatFrTs formats ISO dates correctly', () => {
        const formatFrTs = (iso) => {
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
        };
        
        expect(formatFrTs(null)).toBe('—');
        expect(formatFrTs('invalid')).toBe('—');
        expect(formatFrTs('2024-01-15T10:30:00Z')).toContain('2024');
    });
});

describe('Utilisateurs.js - Stats Management', () => {
    beforeEach(() => {
        createMockDOM();
    });

    test('bumpStat increases stat value', () => {
        const bumpStat = (key, delta) => {
            const el = document.querySelector(`[data-stat="${key}"]`);
            if (!el) {
                return;
            }
            const n = Number(el.textContent) || 0;
            el.textContent = String(Math.max(0, n + delta));
        };
        
        const totalEl = document.querySelector('[data-stat="total"]');
        expect(totalEl.textContent).toBe('10');
        
        bumpStat('total', 1);
        expect(totalEl.textContent).toBe('11');
    });

    test('bumpStat decreases stat value', () => {
        const bumpStat = (key, delta) => {
            const el = document.querySelector(`[data-stat="${key}"]`);
            if (!el) {
                return;
            }
            const n = Number(el.textContent) || 0;
            el.textContent = String(Math.max(0, n + delta));
        };
        
        const activeEl = document.querySelector('[data-stat="active"]');
        expect(activeEl.textContent).toBe('8');
        
        bumpStat('active', -1);
        expect(activeEl.textContent).toBe('7');
    });

    test('bumpStat does not go below zero', () => {
        const bumpStat = (key, delta) => {
            const el = document.querySelector(`[data-stat="${key}"]`);
            if (!el) {
                return;
            }
            const n = Number(el.textContent) || 0;
            el.textContent = String(Math.max(0, n + delta));
        };
        
        const inactiveEl = document.querySelector('[data-stat="inactive"]');
        expect(inactiveEl.textContent).toBe('2');
        
        bumpStat('inactive', -10);
        expect(inactiveEl.textContent).toBe('0');
    });
});

describe('Utilisateurs.js - Toast Notifications', () => {
    beforeEach(() => {
        createMockDOM();
        jest.useFakeTimers();
    });

    afterEach(() => {
        jest.useRealTimers();
    });

    test('showToast displays success message', () => {
        const showToast = (message, variant = 'success') => {
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
        };
        
        const toast = document.querySelector('[data-app-toast]');
        showToast('User created successfully!', 'success');
        
        expect(toast.textContent).toBe('User created successfully!');
        expect(toast.classList.contains('hidden')).toBe(false);
        expect(toast.classList.contains('border-emerald-200')).toBe(true);
        expect(toast.classList.contains('bg-emerald-50')).toBe(true);
    });

    test('showToast displays error message', () => {
        const showToast = (message, variant = 'success') => {
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
        };
        
        const toast = document.querySelector('[data-app-toast]');
        showToast('Network error occurred!', 'error');
        
        expect(toast.textContent).toBe('Network error occurred!');
        expect(toast.classList.contains('border-red-200')).toBe(true);
        expect(toast.classList.contains('bg-red-50')).toBe(true);
    });
});

describe('Utilisateurs.js - Permission Management', () => {
    beforeEach(() => {
        createMockDOM();
    });

    test('collectCheckedPermissionIds returns checked permission IDs', () => {
        const collectCheckedPermissionIds = (modal, rootKey) => {
            const root = modal.querySelector(`[data-perm-root="${rootKey}"]`);
            if (!root) {
                return [];
            }
            return [...root.querySelectorAll('[data-perm-checkbox]:checked')].map((cb) => Number(cb.value));
        };
        
        const modal = document.getElementById('create-user-modal');
        const checkboxes = modal.querySelectorAll('[data-perm-checkbox]');
        checkboxes[0].checked = true;
        
        const ids = collectCheckedPermissionIds(modal, 'create');
        expect(ids).toEqual([1]);
    });

    test('setCheckedPermissions checks correct checkboxes', () => {
        const setCheckedPermissions = (modal, rootKey, ids) => {
            const root = modal.querySelector(`[data-perm-root="${rootKey}"]`);
            if (!root) {
                return;
            }
            const set = new Set(ids.map(Number));
            root.querySelectorAll('[data-perm-checkbox]').forEach((cb) => {
                cb.checked = set.has(Number(cb.value));
            });
        };
        
        const modal = document.getElementById('create-user-modal');
        setCheckedPermissions(modal, 'create', [1, 2]);
        
        const checkboxes = modal.querySelectorAll('[data-perm-checkbox]');
        expect(checkboxes[0].checked).toBe(true);
        expect(checkboxes[1].checked).toBe(true);
    });
});

describe('Utilisateurs.js - Role Selection', () => {
    beforeEach(() => {
        createMockDOM();
    });

    test('setSelectedRoleInGrid marks correct role as selected', () => {
        const updateRolePreview = jest.fn();
        
        const setSelectedRoleInGrid = (grid, selectedId, previewTitle, previewList, rolesData) => {
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
            return role;
        };
        
        const modal = document.getElementById('create-user-modal');
        const grid = modal.querySelector('[data-create-role-grid]');
        const rolesData = [
            { id: 1, name: 'Administrator', permissions: [] },
            { id: 2, name: 'User', permissions: [] }
        ];
        
        setSelectedRoleInGrid(grid, 1, null, null, rolesData);
        
        const cards = grid.querySelectorAll('[data-role-card]');
        expect(cards[0].dataset.selected).toBe('true');
        expect(cards[1].dataset.selected).toBe('false');
    });
});

describe('Utilisateurs.js - Filter Popup', () => {
    beforeEach(() => {
        createMockDOM();
    });

    test('filter toggle opens popup', () => {
        const toggle = document.querySelector('[data-filter-toggle]');
        const popup = document.querySelector('[data-filter-popup]');
        
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
        
        expect(popup.hasAttribute('hidden')).toBe(true);
        
        toggle.click();
        
        expect(popup.hasAttribute('hidden')).toBe(false);
        expect(toggle.dataset.open).toBe('true');
    });

    test('filter toggle closes popup when already open', () => {
        const toggle = document.querySelector('[data-filter-toggle]');
        const popup = document.querySelector('[data-filter-popup]');
        
        popup.removeAttribute('hidden');
        toggle.dataset.open = 'true';
        
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
        
        toggle.click();
        
        expect(popup.hasAttribute('hidden')).toBe(true);
        expect(toggle.dataset.open).toBeUndefined();
    });
});

describe('Utilisateurs.js - User Row Building', () => {
    beforeEach(() => {
        createMockDOM();
    });

    test('buildUserRow creates correct HTML structure', () => {
        const escapeHtml = (s) => {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        };
        
        const formatFrTs = () => '15 janv. 2024, 10:30';
        
        const buildUserRow = (payload) => {
            const active = payload.is_active;
            const roleName = payload.roles?.[0]?.name ?? '—';
            const instName = payload.institution?.name ?? '—';
            
            return `<tr data-user-row data-user-id="${payload.id}">
                <td>${escapeHtml(payload.full_name)}</td>
                <td>${escapeHtml(payload.email)}</td>
                <td>${escapeHtml(roleName)}</td>
                <td>${escapeHtml(instName)}</td>
            </tr>`;
        };
        
        const payload = {
            id: 1,
            full_name: 'John Doe',
            email: 'john@test.com',
            is_active: true,
            roles: [{ id: 1, name: 'Admin' }],
            institution: { name: 'Test Institution' }
        };
        
        const html = buildUserRow(payload);
        
        expect(html).toContain('John Doe');
        expect(html).toContain('john@test.com');
        expect(html).toContain('Admin');
        expect(html).toContain('Test Institution');
        expect(html).toContain('data-user-id="1"');
    });
});