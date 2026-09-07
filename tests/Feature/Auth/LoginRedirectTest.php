<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ログイン成功後の遷移先と認証エラーを確認するフィーチャーテスト。
 *
 * 未認証時にGoogle Workspace画面へアクセスしていても、ログイン後はLMSの
 * ロール別トップへ移動するための共通ダッシュボードへ遷移することを検証する。
 */
final class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 保存済みのGoogle Chat遷移先を無視して共通ダッシュボードへ遷移することを確認する。
     *
     * 前提: 有効な生徒ユーザーと、未認証時に保存されたGoogle Chatの遷移先を準備する。
     * 処理: 正しいメールアドレスとパスワードでログインする。
     * 期待結果: Google Chatではなく共通ダッシュボードへ遷移し、保存済み遷移先が破棄される。
     */
    public function test_login_ignores_google_chat_intended_url(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->withSession([
                'url.intended' => route('google-workspace.chat.index'),
            ])
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response
            ->assertRedirectToRoute('dashboard')
            ->assertSessionMissing('url.intended');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * JSONログインのFortify互換レスポンスを維持することを確認する。
     *
     * 前提: 有効な生徒ユーザーを準備する。
     * 処理: JSONを受け取るリクエストとして正しい認証情報を送信する。
     * 期待結果: 二要素認証なしを示す既存形式のJSONを返し、ユーザーが認証される。
     */
    public function test_json_login_keeps_fortify_response_format(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'two_factor' => false,
            ]);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * 利用停止ユーザーには専用メッセージを表示して認証しないことを確認する。
     */
    public function test_suspended_user_receives_dedicated_login_error(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Suspended,
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'このアカウントは現在利用できません',
            ]);

        $this->assertGuest();
    }

    /**
     * パスワードに全角文字が含まれる場合は半角文字エラーとして認証しないことを確認する。
     */
    public function test_full_width_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'ｐａｓｓｗｏｒｄ',
            ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'password' => 'パスワードは半角文字で入力してください',
            ]);

        $this->assertGuest();
    }
}
