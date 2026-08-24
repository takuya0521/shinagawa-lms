<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 共通エラーページのHTTPステータスと表示内容を確認するフィーチャーテスト。
 *
 * C-004～C-007について、本番用の障害発生ルートを追加せず、テスト実行中だけ
 * 419・500・503を発生させてLaravelの例外処理と専用エラー画面を確認する。
 */
final class ErrorPageRenderingTest extends TestCase
{
    /**
     * C-004の存在しないURLで404専用画面が表示されることを確認する。
     *
     * 前提: APP_DEBUGを無効にし、存在しないテスト用URLを使用する。
     * 処理: 存在しないURLへGETリクエストを送信する。
     * 期待結果: HTTP 404となり、404コード・案内文・復旧操作が専用画面に表示される。
     */
    public function test_c004_not_found_page_is_rendered(): void
    {
        config(['app.debug' => false]);

        $this
            ->get('/__error-page-test__/not-found')
            ->assertNotFound()
            ->assertSeeText('404')
            ->assertSeeText('ページが見つかりません')
            ->assertSeeText('URLが変更されたか、対象データが削除された可能性があります。')
            ->assertSeeText('トップへ戻る')
            ->assertSee('href="'.url('/').'"', false)
            ->assertSeeText('前の画面へ戻る')
            ->assertSee('data-history-back', false);
    }

    /**
     * C-005の419専用画面が表示されることを確認する。
     *
     * 前提: APP_DEBUGを無効にし、テスト実行中だけHTTP 419を発生させるルートを登録する。
     * 処理: テスト専用ルートへGETリクエストを送信してHTTP 419を発生させる。
     * 期待結果: HTTP 419となり、セッション有効期限切れの案内と復旧操作が表示される。
     */
    public function test_c005_session_expired_page_is_rendered(): void
    {
        config(['app.debug' => false]);
        $path = $this->registerErrorRoute(419);

        $this
            ->get($path)
            ->assertStatus(419)
            ->assertSeeText('419')
            ->assertSeeText('セッションの有効期限が切れました')
            ->assertSeeText('安全のため処理を中断しました。')
            ->assertSeeText('トップへ戻る')
            ->assertSee('href="'.url('/').'"', false)
            ->assertSeeText('前の画面へ戻る')
            ->assertSee('data-history-back', false);
    }

    /**
     * C-006の500専用画面が表示されることを確認する。
     *
     * 前提: APP_DEBUGを無効にし、テスト実行中だけHTTP 500を発生させるルートを登録する。
     * 処理: テスト専用ルートへGETリクエストを送信してHTTP 500を発生させる。
     * 期待結果: HTTP 500となり、システムエラーの案内と復旧操作が表示される。
     */
    public function test_c006_system_error_page_is_rendered(): void
    {
        config(['app.debug' => false]);
        $path = $this->registerErrorRoute(500);

        $this
            ->get($path)
            ->assertStatus(500)
            ->assertSeeText('500')
            ->assertSeeText('処理中にエラーが発生しました')
            ->assertSeeText('時間を置いて再度お試しください。')
            ->assertSeeText('トップへ戻る')
            ->assertSee('href="'.url('/').'"', false)
            ->assertSeeText('前の画面へ戻る')
            ->assertSee('data-history-back', false);
    }

    /**
     * C-007の503専用画面が表示されることを確認する。
     *
     * 前提: APP_DEBUGを無効にし、テスト実行中だけHTTP 503を発生させるルートを登録する。
     * 処理: テスト専用ルートへGETリクエストを送信してHTTP 503を発生させる。
     * 期待結果: HTTP 503となり、メンテナンス中の案内と復旧操作が表示される。
     */
    public function test_c007_maintenance_page_is_rendered(): void
    {
        config(['app.debug' => false]);
        $path = $this->registerErrorRoute(503);

        $this
            ->get($path)
            ->assertStatus(503)
            ->assertSeeText('503')
            ->assertSeeText('現在メンテナンス中です')
            ->assertSeeText('メンテナンス終了後に再度アクセスしてください。')
            ->assertSeeText('トップへ戻る')
            ->assertSee('href="'.url('/').'"', false)
            ->assertSeeText('前の画面へ戻る')
            ->assertSee('data-history-back', false);
    }

    /**
     * テスト実行中だけ指定HTTPステータスを発生させるルートを登録する。
     *
     * 本番用routesファイルには変更を加えず、Featureテストのアプリケーション内だけで
     * エラー画面の描画経路を安全に再現する。
     *
     * @param  int  $status  発生させるHTTPステータスコード
     * @return string 登録したテスト専用ルートのパス
     */
    private function registerErrorRoute(int $status): string
    {
        $path = "/__error-page-test__/{$status}";

        Route::get($path, static function () use ($status): never {
            abort($status);
        });

        return $path;
    }
}
