import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

const standalonePageStyles = [
    'resources/css/pages/auth/login.css',
    'resources/css/pages/dashboard/student.css',
    'resources/css/pages/errors/403.css',
    'resources/css/pages/errors/404.css',
    'resources/css/pages/errors/419.css',
    'resources/css/pages/errors/500.css',
    'resources/css/pages/errors/503.css',
    'resources/css/pages/google-workspace/workspace.css',
];

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/layouts/authenticated-bundle.css',
                ...standalonePageStyles,
                'resources/js/app.js',
                'resources/js/google-workspace.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
