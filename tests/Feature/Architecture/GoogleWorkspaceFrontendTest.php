<?php

namespace Tests\Feature\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Google Workspace専用フロントエンドの通常遷移・分割読み込み方針を確認する。
 */
final class GoogleWorkspaceFrontendTest extends TestCase
{
    /**
     * 外部APIを伴うGoogle Workspace画面をhoverやfocusで先読みしないことを確認する。
     *
     * 前提: Google Workspace専用JavaScriptとBladeを読み込む。
     * 処理: prefetch関数・専用ヘッダー・先読み属性が残っていないことを検索する。
     * 期待結果: 明示的なクリック前にバックグラウンドGETを発生させる実装が存在しない。
     */
    public function test_workspace_does_not_prefetch_api_backed_pages(): void
    {
        $script = file_get_contents(resource_path('js/google-workspace.js'));
        $views = collect([
            ...File::allFiles(resource_path('views/google-workspace')),
            ...File::allFiles(resource_path('views/components/google-workspace')),
        ])
            ->map(static fn (\SplFileInfo $file): string|false => file_get_contents($file->getPathname()))
            ->filter(static fn (string|false $contents): bool => is_string($contents))
            ->implode("\n");

        $this->assertIsString($script);
        $this->assertStringNotContainsString('prefetchWorkspaceLink', $script);
        $this->assertStringNotContainsString('X-Google-Workspace-Prefetch', $script);
        $this->assertStringNotContainsString('data-workspace-prefetch', $views);
    }

    /**
     * Google Workspace画面だけが専用アセットを読み込むことを確認する。
     *
     * 前提: 認証済み共通レイアウトを読み込む。
     * 処理: 共通Viteエントリとページ専用アセット追加処理を検索する。
     * 期待結果: 共通バンドルへ必要なページ専用CSSとJavaScriptだけを追加できる。
     */
    public function test_workspace_assets_are_loaded_conditionally(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertIsString($layout);
        $this->assertStringContainsString('$pageStyle = trim($__env->yieldContent(\'page-style\'))', $layout);
        $this->assertStringContainsString('$pageScript = trim($__env->yieldContent(\'page-script\'))', $layout);
        $this->assertStringContainsString('resources/css/layouts/authenticated-bundle.css', $layout);
        $this->assertStringContainsString('$viteEntries[] = $pageStyle;', $layout);
        $this->assertStringContainsString('$viteEntries[] = $pageScript;', $layout);
        $this->assertStringContainsString('@vite($viteEntries)', $layout);
    }

    /**
     * Google APIデータがページ本体とは別リクエストで取得されることを確認する。
     *
     * 前提: Workspace専用JavaScriptと主要Bladeを読み込む。
     * 処理: 非同期領域属性、fetch処理、専用contentルート参照を検索する。
     * 期待結果: Google Workspace各サービスの初期HTML生成がGoogle API完了待ちから分離されている。
     */
    public function test_workspace_google_data_is_loaded_after_page_shell(): void
    {
        $script = file_get_contents(resource_path('js/google-workspace.js'));
        $views = collect([
            resource_path('views/google-workspace/drive/index.blade.php'),
            resource_path('views/google-workspace/drive/show.blade.php'),
            resource_path('views/google-workspace/classroom/index.blade.php'),
            resource_path('views/google-workspace/classroom/show.blade.php'),
            resource_path('views/google-workspace/calendar/index.blade.php'),
            resource_path('views/google-workspace/calendar/show.blade.php'),
            resource_path('views/google-workspace/chat/index.blade.php'),
            resource_path('views/google-workspace/chat/show.blade.php'),
            resource_path('views/google-workspace/meet/index.blade.php'),
            resource_path('views/google-workspace/forms/index.blade.php'),
            resource_path('views/google-workspace/forms/show.blade.php'),
        ])->map(static fn (string $path): string|false => file_get_contents($path))
            ->filter(static fn (string|false $contents): bool => is_string($contents))
            ->implode("\n");

        $this->assertIsString($script);
        $this->assertStringContainsString('data-workspace-async-region', $views);
        $this->assertStringContainsString('window.fetch(url', $script);
        $this->assertStringContainsString('X-Google-Workspace-Async', $script);
        $this->assertStringContainsString("route('google-workspace.drive.content'", $views);
        $this->assertStringContainsString("route('google-workspace.classroom.content'", $views);
        $this->assertStringContainsString("route('google-workspace.classroom.show-content'", $views);
        $this->assertStringContainsString("route('google-workspace.calendar.content'", $views);
        $this->assertStringContainsString("route('google-workspace.chat.content'", $views);
        $this->assertStringContainsString("route('google-workspace.meet.content'", $views);
        $this->assertStringContainsString("route('google-workspace.forms.content'", $views);
        $this->assertStringContainsString("route('google-workspace.forms.show-content'", $views);
    }

    /**
     * staleキャッシュを即表示した後にfresh-only再検証することを確認する。
     *
     * 前提: Workspace専用JavaScriptを読み込む。
     * 処理: stale応答ヘッダー判定とSWR再取得パラメーターを検索する。
     * 期待結果: 期限切れAPI結果でも表示を待たせず、裏で最新版へ更新できる。
     */
    public function test_workspace_revalidates_stale_fragments_in_background(): void
    {
        $script = file_get_contents(resource_path('js/google-workspace.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('X-Google-Workspace-Stale', $script);
        $this->assertStringContainsString("swr_refresh', '1'", $script);
        $this->assertStringContainsString('revalidateWorkspaceRegion', $script);
        $this->assertStringContainsString("cache: 'no-store'", $script);
    }
}
