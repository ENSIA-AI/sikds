import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    base: '/build/',
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/permissions.js',
                'resources/js/pages/roles.js',
                'resources/js/pages/institutions.js',
                'resources/js/pages/users.js',
                'resources/js/pages/documents.js',
                'resources/js/pages/tags.js',
                'resources/js/pages/audits.js',
                'resources/js/pages/rag.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
});
