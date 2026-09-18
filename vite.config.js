import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { createSvgIconsPlugin } from 'vite-plugin-svg-icons-ng';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        createSvgIconsPlugin({
            iconDirs: [path.resolve(process.cwd(), 'resources/svg/icons')],
            symbolId: 'icon-[name]',
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});