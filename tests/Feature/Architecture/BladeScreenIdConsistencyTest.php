<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

/**
 * 提出用画面に開発用の画面IDが表示されないことを確認するアーキテクチャテスト。
 *
 * 設計書で管理する画面IDや非機能要件IDを、利用者向けのBladeへ表示しないことを検証する。
 */
final class BladeScreenIdConsistencyTest extends TestCase
{
    /**
     * すべてのBladeに開発用IDが表示されないことを確認する。
     *
     * 前提: 提出対象のBladeファイルがresources/views配下に配置されている。
     * 処理: Blade内で画面ID・要件IDとして表示される文字列を検索する。
     * 期待結果: 開発用IDを表示するBladeが1件も存在しない。
     */
    public function test_submission_blades_do_not_display_development_ids(): void
    {
        $violations = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views')),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! is_string($contents)) {
                continue;
            }

            preg_match_all(
                '/>\s*([CAST]-\d{3}|NFR-\d{3}|CP-\d{3})(?:（[^<]*）)?\s*</u',
                $contents,
                $matches,
            );

            if ($matches[1] === []) {
                continue;
            }

            $relativePath = str_replace(
                '\\',
                '/',
                substr($file->getPathname(), strlen(resource_path('views')) + 1),
            );
            $violations[$relativePath] = array_values(array_unique($matches[1]));
        }

        self::assertSame(
            [],
            $violations,
            '提出用画面に開発用IDが表示されています。',
        );
    }
}
