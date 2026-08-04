<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 管理者向けユーザー管理機能を確認するフィーチャーテスト。
 *
 * 登録・更新、メール重複、利用停止・再開、自己停止禁止を検証する。
 */
final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者がユーザーを登録できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.users.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、対象条件が真になることを確認する。
     */
    public function test_admin_can_create_user(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => '登録対象教員',
                'email' => 'teacher@example.com',
                'role' => UserRole::Teacher->value,
                'status' => UserStatus::Active->value,
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
            ]);

        $user = User::query()
            ->where('email', 'teacher@example.com')
            ->firstOrFail();

        $response->assertRedirectToRoute(
            'admin.users.edit',
            $user,
        );

        $this->assertDatabaseHas('users', [
            'name' => '登録対象教員',
            'email' => 'teacher@example.com',
            'role' => UserRole::Teacher->value,
            'status' => UserStatus::Active->value,
        ]);

        $this->assertTrue(
            Hash::check('Password@123', $user->password),
        );
    }

    /**
     * 同じメールアドレスを重複登録できないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_duplicate_email_cannot_be_registered(): void
    {
        $admin = $this->createAdmin();

        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => '重複ユーザー',
                'email' => 'duplicate@example.com',
                'role' => UserRole::Student->value,
                'status' => UserStatus::Active->value,
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
            ]);

        $response
            ->assertRedirectToRoute('admin.users.create')
            ->assertSessionHasErrors('email');
    }

    /**
     * パスワードを変更せずにユーザー情報を更新できることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、取得値が期待値と一致する、対象条件が真になることを確認する。
     */
    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'name' => '変更前氏名',
            'email' => 'before@example.com',
            'password' => Hash::make('Original@123'),
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => '変更後氏名',
                'email' => 'after@example.com',
                'role' => UserRole::Teacher->value,
                'status' => UserStatus::Active->value,
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirectToRoute(
            'admin.users.edit',
            $user,
        );

        $user->refresh();

        $this->assertSame('変更後氏名', $user->name);
        $this->assertSame('after@example.com', $user->email);
        $this->assertSame(UserRole::Teacher, $user->role);

        $this->assertTrue(
            Hash::check('Original@123', $user->password),
        );
    }

    /**
     * 管理者が別ユーザーの利用を停止できることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.status.update`へPATCHリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_suspend_another_user(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status.update', $user),
                [
                    'status' => UserStatus::Suspended->value,
                ],
            );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::Suspended->value,
        ]);
    }

    /**
     * 管理者が停止中のユーザーを再有効化できることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.status.update`へPATCHリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_reactivate_suspended_user(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'status' => UserStatus::Suspended,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status.update', $user),
                [
                    'status' => UserStatus::Active->value,
                ],
            );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::Active->value,
        ]);
    }

    /**
     * 管理者が自身のアカウントを利用停止にできないことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.users.status.update`へPATCHリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返る、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_cannot_suspend_their_own_account(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(
                route('admin.users.status.update', $admin),
                [
                    'status' => UserStatus::Suspended->value,
                ],
            );

        $response
            ->assertRedirectToRoute('admin.users.index')
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'status' => UserStatus::Active->value,
        ]);
    }

    /**
     * テストで使用する有効な管理者ユーザーを作成して返す。
     */
    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
