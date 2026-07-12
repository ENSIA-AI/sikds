/**
 * Audit log page — filters auto-apply + export modal. Extracted from
 * resources/views/audits/index.blade.php so the page ships as a Vite module
 * instead of an inline script (CSP hardening). Selected filter state is
 * passed via the #audit-selected-events / #audit-selected-statuses JSON tags.
 */
function auditFilters() {
    return {
        eventOpen: false,
        statusOpen: false,
        selectedEvents: JSON.parse(document.getElementById('audit-selected-events')?.textContent || '[]'),
        selectedStatuses: JSON.parse(document.getElementById('audit-selected-statuses')?.textContent || '[]'),
    };
}
// Expose globally for Alpine.js x-data binding
window.auditFilters = auditFilters;

document.addEventListener('DOMContentLoaded', function () {

    const filterForm = document.getElementById('audit-filter-form');
    const dateInput = filterForm?.querySelector('input[name="date"]');
    const userInput = filterForm?.querySelector('input[name="user"]');
    const applyBtn = filterForm?.querySelector('button[type="submit"]');

    const submitFilters = () => {
        if (!filterForm) return;
        applyBtn?.classList.add('opacity-70');
        applyBtn?.setAttribute('disabled', 'disabled');
        filterForm.submit();
    };

    dateInput?.addEventListener('change', submitFilters);

    // Auto-apply when any event_type or result checkbox toggles inside the multi-selects.
    let multiSelectDebounceTimer = null;
    filterForm?.querySelectorAll('input[type="checkbox"][name="event_type[]"], input[type="checkbox"][name="result[]"]').forEach((cb) => {
        cb.addEventListener('change', function () {
            if (multiSelectDebounceTimer) clearTimeout(multiSelectDebounceTimer);
            // Small debounce so a rapid multi-click batches into a single submit.
            multiSelectDebounceTimer = setTimeout(submitFilters, 250);
        });
    });

    let userDebounceTimer = null;
    userInput?.addEventListener('input', function () {
        if (userDebounceTimer) clearTimeout(userDebounceTimer);
        userDebounceTimer = setTimeout(submitFilters, 450);
    });

    userInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitFilters();
        }
    });



    const modal = document.getElementById('export-audit-modal');
    const openBtn = document.getElementById('open-export-modal');
    const closeBtn = document.getElementById('close-export-modal');
    const cancelBtn = document.getElementById('cancel-export-modal');
    const overlay = document.getElementById('export-modal-overlay');

    if (!modal || !openBtn) return;

    const open = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };
    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    openBtn.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    overlay?.addEventListener('click', close);
});
