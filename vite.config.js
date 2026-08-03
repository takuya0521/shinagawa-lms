import { readdirSync } from 'node:fs';
import { join } from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

/**
 * 指定ディレクトリ配下のCSSを再帰的に収集する。
 *
 * ページ追加時にVite設定への追記が漏れないよう、ページ専用CSSだけを自動登録する。
 *
 * @param {string} directory
 * @returns {string[]}
 */
function collectPageStyles(directory) {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const entryPath = join(directory, entry.name);

        if (entry.isDirectory()) {
            return collectPageStyles(entryPath);
        }

        return entry.isFile() && entry.name.endsWith('.css')
            ? [entryPath.replaceAll('\\', '/')]
            : [];
    });
}

const pageStyleEntries = collectPageStyles('resources/css/pages');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/layouts/authenticated.css',
                'resources/css/layouts/guest.css',
                'resources/css/components/utility-compatibility.css',
                'resources/css/components/forms.css',
                'resources/css/components/buttons.css',
                'resources/css/components/feedback.css',
                'resources/js/app.js',
                ...pageStyleEntries,
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
            // Bladeキャッシュの更新では再ビルドが不要なため、監視対象から除外する。
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
