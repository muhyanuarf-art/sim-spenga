import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        // esbuild's default CSS minifier corrupts the Unicode escape content
        // inside Font Awesome's CSS custom properties (e.g. .fa-lock{--fa:"\f023"}
        // becomes --fa:"" after minify, so icons render blank). Lightning CSS
        // has the same issue on this file, so CSS minification is disabled —
        // the size cost is small (~2KB gzip) and icons render correctly.
        cssMinify: false,
    },
});
