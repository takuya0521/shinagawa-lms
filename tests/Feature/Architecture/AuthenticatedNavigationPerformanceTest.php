<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

/**
 * 認証済み画面の共通アセットとナビゲーション処理が軽量な構成であることを確認する。
 */
final class AuthenticatedNavigationPerformanceTest extends TestCase
{
    /**
     * 通常画面のCSSを共通バンドルで再利用することを確認する。
     *
     * 前提: 認証済みレイアウトとVite設定を読み込む。
     * 処理: 共通CSSバンドルの指定とページCSS自動エントリ生成の有無を確認する。
     * 期待結果: 通常画面の遷移ごとに個別CSSエントリを追加取得する構成になっていない。
     */
    public function test_authenticated_pages_reuse_shared_css_bundle(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $viteConfig = file_get_contents(base_path('vite.config.js'));

        $this->assertIsString($layout);
        $this->assertIsString($viteConfig);
        $this->assertStringContainsString(
            'resources/css/layouts/authenticated-bundle.css',
            $layout,
        );
        $this->assertStringContainsString(
            'resources/css/layouts/authenticated-bundle.css',
            $viteConfig,
        );
        $this->assertStringNotContainsString('collectPageStyles', $viteConfig);
    }

    /**
     * サイドバー位置保持が高頻度イベントを使わないことを確認する。
     *
     * 前提: 認証済み共通JavaScriptを読み込む。
     * 処理: scroll・resize監視を避け、クリック委譲とブレークポイント変更監視を確認する。
     * 期待結果: 画面遷移前後に同期処理やレイアウト更新を繰り返さない。
     */
    public function test_sidebar_navigation_avoids_high_frequency_event_handlers(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($script);
        $this->assertStringNotContainsString("addEventListener('scroll'", $script);
        $this->assertStringNotContainsString("addEventListener('resize'", $script);
        $this->assertStringContainsString("document.addEventListener('click'", $script);
        $this->assertStringContainsString("mobileLayout.addEventListener('change'", $script);
    }
}
