import { initFlowbite } from 'flowbite';
import Alpine from 'alpinejs';
import './custom.js';
import './sidebar.js';

// Portal id-based (non-Alpine) modals to <body> so their fixed backdrop always
// covers the full viewport — escaping any layout stacking/containing context that
// would otherwise dim only the main content area. Node identity, id, listeners and
// the `hidden` attribute are preserved by appendChild, so page JS keeps working.
// (Alpine-scoped modals use x-teleport instead, which preserves their component scope.)
function portalModalsToBody() {
    document.querySelectorAll('[data-modal-portal]').forEach((el) => {
        if (el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    window.Alpine = Alpine;
    Alpine.start();
    initFlowbite();
    portalModalsToBody();
});
