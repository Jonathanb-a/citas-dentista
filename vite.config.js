import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            safelist: ['rotate-45', '-rotate-45', 'translate-y-2', '-translate-y-2', 'opacity-0'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});