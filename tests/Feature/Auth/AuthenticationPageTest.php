<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * ログイン画面と未認証アクセス制御を確認するフィーチャーテスト。
 *
 * ホームからの遷移、ログイン画面表示、ダッシュボード保護を検証する。
 */
final class AuthenticationPageTest extends TestCase
{
    /**
     * 未ログインユーザーがホームへアクセスするとログイン画面へ遷移することを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した画面へリダイレクトされることを確認する。
     */
    public function test_guest_is_redirected_from_home_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('login');
    }

    /**
     * 未ログインユーザーがログイン画面を表示できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: HTTP 200の正常レスポンスとなることを確認する。
     */
    public function test_guest_can_view_login_screen(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    /**
     * 未ログインユーザーがダッシュボードへ直接アクセスできないことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した画面へリダイレクトされることを確認する。
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirectToRoute('login');
    }
}
