import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    // 👇 Descomenta este bloque "server" cuando quieras probar en el celular
    // Recuerda poner tu IP actual (ipconfig) en hmr.host
    /*
    server: {
        host: '0.0.0.0',
        cors: true,
        hmr: {
            host: '192.168.1.66',
        },
    },
    */
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            safelist: ['rotate-45', '-rotate-45', 'translate-y-2', '-translate-y-2', 'opacity-0'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});