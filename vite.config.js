import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    build: {
        chunkSizeWarningLimit: 3072, // 3MB
        assetsInlineLimit: 0
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/images/logo.svg', 'resources/images/team/rob.jpg', 'resources/images/team/mathieu.png', 'resources/js/matter.min.js', 'resources/images/games/kabonk!-available-on-steam.png'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
