<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

/**
 * 共通フィードバック表示の重複を防止するアーキテクチャテスト。
 *
 * 成功・状態メッセージが共通レイアウトだけで描画され、各画面に重複実装がないことを検証する。
 */
final class FeedbackRenderingConsistencyTest extends TestCase
{
    /**
     * 状態・成功メッセージが共通レイアウトだけで描画されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    public function test_status_and_success_flash_messages_are_rendered_only_by_the_common_layout(): void
    {
        $violations = [];
        $viewsRoot = resource_path('views');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsRoot),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relativePath = str_replace(
                '\\',
                '/',
                substr($file->getPathname(), strlen($viewsRoot) + 1),
            );

            if ($relativePath === 'layouts/app.blade.php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! is_string($contents)) {
                continue;
            }

            if (preg_match("/session\('(status|success)'\)/", $contents) === 1) {
                $violations[] = $relativePath;
            }
        }

        self::assertSame([], $violations);
    }
}
