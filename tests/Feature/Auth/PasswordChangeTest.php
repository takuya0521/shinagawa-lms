<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ログイン済みユーザーのパスワード変更機能を確認するフィーチャーテスト。
 *
 * 画面表示、正常更新、現在パスワード・確認入力・強度検証、未認証アクセス制御を確認する。
 */
final class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログイン済みユーザーがパスワード変更画面を表示できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `account.password.edit`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_authenticated_user_can_view_password_change_page(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSeeText('パスワード変更')
            ->assertSeeInOrder(['変更する', '戻る'])
            ->assertSee('href="'.route('dashboard').'"', false);
    }

    /**
     * ログイン済みユーザーが正しい現在パスワードで新しいパスワードへ変更できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `account.password.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、対象条件が真になることを確認する。
     */
    public function test_authenticated_user_can_change_password(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHas('success', 'パスワードを変更しました。');

        $this->assertTrue(
            Hash::check('NewPassword123', $user->refresh()->password),
        );
        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $user->id,
            'action' => 'change_password',
            'target_table' => 'users',
            'target_id' => $user->id,
        ]);
    }

    /**
     * 現在パスワードが誤っている場合にパスワード変更を拒否することを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `account.password.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返る、対象条件が真になることを確認する。
     */
    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(
            Hash::check('password', $user->refresh()->password),
        );
    }

    /**
     * パスワード変更時に確認入力と強度条件が必須であることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `account.password.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_password_confirmation_and_strength_are_required(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'weak',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('password');
    }

    /**
     * 未ログインユーザーがパスワード変更画面からログイン画面へ遷移することを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `account.password.edit`へGETリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされることを確認する。
     */
    public function test_guest_is_redirected_from_password_change_page(): void
    {
        $this
            ->get(route('account.password.edit'))
            ->assertRedirectToRoute('login');
    }

    /**
     * パスワード変更テストで使用する有効なユーザーを作成して返す。
     */
    private function user(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'password' => Hash::make('password'),
        ]);
    }
}
