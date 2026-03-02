//initialize flowbite

import 'flowbite';
// Import jQuery first
import $ from 'jquery';


document.addEventListener('livewire:navigated', () => {
    // Re-initialize all Flowbite components.
    if (typeof initFlowbite === 'function') {
        initFlowbite();
    }

});
window.addEventListener('page-reload', function(e) {
    window.location.reload();
});


     window.addEventListener('close-lieu-modal', () => {
            const modalId = 'lieu-modal';
            const modal = document.getElementById(modalId);
            const closeBtn = modal?.querySelector(`[data-modal-hide="${modalId}"]`);
            closeBtn?.click();
            modal?.classList.add('hidden');
        });


            document.addEventListener('DOMContentLoaded', function () {
        window.addEventListener('lieu-edit', () => {
            const modal = document.getElementById('lieu-modal');
            if (modal) {
                // Using Flowbite modal
                const instance = Flowbite?.instances?.getInstance(modal);
                if (instance) {
                    instance.show();
                } else {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            }
        });

    });


