function normalizeText(s) {
    return String(s ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function setButtonState(btn, active) {
    btn.classList.remove('bg-[#030213]', 'text-white', 'bg-white', 'text-black', 'hover:bg-black/[0.03]');

    if (active) {
        btn.classList.add('bg-[#030213]', 'text-white');
    } else {
        btn.classList.add('bg-white', 'text-black', 'hover:bg-black/[0.03]');
    }
}

function wirePermissionsPage(root) {
    const search = root.querySelector('[data-permissions-search]');
    const buttons = Array.from(root.querySelectorAll('[data-permissions-filter-button]'));
    const cards = Array.from(root.querySelectorAll('[data-permissions-category-card]'));
    const empty = root.querySelector('[data-permissions-empty]');
    const reset = root.querySelector('[data-permissions-reset]');

    let selectedKey = 'all';

    function apply() {
        const q = normalizeText(search?.value ?? '');
        let visibleAny = false;

        cards.forEach((card) => {
            const cat = card.getAttribute('data-category-key') || '';
            const catMatches = selectedKey === 'all' || selectedKey === cat;

            const items = Array.from(card.querySelectorAll('[data-permissions-item]'));
            let visibleItems = 0;
            items.forEach((item) => {
                const hay = normalizeText(item.getAttribute('data-search-haystack') || '');
                const match = q.length === 0 || hay.includes(q);
                item.classList.toggle('hidden', !match);
                if (match) visibleItems += 1;
            });

            const showCard = catMatches && (q.length === 0 ? true : visibleItems > 0);
            card.classList.toggle('hidden', !showCard);
            if (showCard) visibleAny = true;
        });

        if (empty) {
            empty.classList.toggle('hidden', visibleAny);
        }
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            selectedKey = btn.getAttribute('data-filter-key') || 'all';
            buttons.forEach((b) => setButtonState(b, (b.getAttribute('data-filter-key') || 'all') === selectedKey));
            apply();
        });
    });

    search?.addEventListener('input', () => apply());
    reset?.addEventListener('click', () => {
        selectedKey = 'all';
        if (search) {
            search.value = '';
            search.focus();
        }
        buttons.forEach((b) => setButtonState(b, (b.getAttribute('data-filter-key') || 'all') === selectedKey));
        apply();
    });

    // Initialize selected button state
    buttons.forEach((b) => setButtonState(b, (b.getAttribute('data-filter-key') || 'all') === selectedKey));
    apply();
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-permissions-page]');
    if (root) {
        wirePermissionsPage(root);
    }
});

