import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    // En développement, le serveur Vite relaie les appels /api
    // vers le serveur Laravel (php artisan serve) : aucune
    // configuration CORS n'est nécessaire.
    server: {
        proxy: {
            '/api': 'http://127.0.0.1:8000',
        },
    },
});
