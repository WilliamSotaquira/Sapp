import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/service-requests/create.js',
                'resources/js/service-requests/edit.js' // Para el futuro
            ],
            refresh: true,
        }),
    ],
});
