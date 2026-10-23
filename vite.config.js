import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are downloaded at build time and served from /build, so visitors never hit a font CDN.
// Two families only: Young Serif for headings (a warm, bookish serif with schoolhouse character)
// and Plus Jakarta Sans for reading and the dashboard.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Young Serif', {
                    alias: 'display',
                    variable: '--ff-display',
                    weights: [400],
                    subsets: ['latin'],
                    preload: [{ weight: 400 }],
                    fallbacks: ['Georgia', 'Cambria', 'serif'],
                }),
                bunny('Plus Jakarta Sans', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 500, 600, 700, 800],
                    subsets: ['latin'],
                    preload: [{ weight: 400 }, { weight: 700 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'Segoe UI', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
