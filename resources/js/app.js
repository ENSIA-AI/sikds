import { initFlowbite } from 'flowbite';
import Alpine from 'alpinejs';
import './custom.js';
import './sidebar.js';

document.addEventListener('DOMContentLoaded', () => {
    window.Alpine = Alpine;
    Alpine.start();
    initFlowbite();
});
