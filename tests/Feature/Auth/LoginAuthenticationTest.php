<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ログイン認証とロール別遷移を確認するフィーチャーテスト。
 *
 * 単体テスト仕様書 C-001 の正常ログイン、認証失敗、利用停止ユーザー拒否を
 * 実際の /login へのPOSTで確認する。
 */
final class LoginAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が正しい認証情報でログインし、管理者ダッシュボードへ遷移できることを確認する。
     */
    public function test_admin_can_login_and_reach_admin_dashboard(): void
    {
        $this->assertRoleLogin(UserRole::Admin, 'admin.dashboard');
    }

    /**
     * 教員が正しい認証情報でログインし、教員ダッシュボードへ遷移できることを確認する。
     */
    public function test_teacher_can_login_and_reach_teacher_dashboard(): void
    {
        $this->assertRoleLogin(UserRole::Teacher, 'teacher.dashboard');
    }

    /**
     * 生徒が正しい認証情報でログインし、生徒トップへ遷移できることを確認する。
     */
    public function test_student_can_login_and_reach_student_dashboard(): void
    {
        $this->assertRoleLogin(UserRole::Student, 'student.dashboard');
    }

    /**
     * 登録済みメールアドレスでもパスワードが誤っている場合はログインできないことを確認する。
     */
    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'registered@example.test',
            'password' => Hash::make('CorrectPass123!'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'WrongPass123!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        $this->assertGuest();
    }

    /**
     * 未登録メールアドレスではログインできないことを確認する。
     */
    public function test_login_rejects_unregistered_email(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'not-registered@example.test',
                'password' => 'Test1234!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        $this->assertGuest();
    }

    /**
     * 利用停止中のユーザーは正しいパスワードでも専用メッセージを表示してログインできないことを確認する。
     */
    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.test',
            'password' => Hash::make('CorrectPass123!'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Suspended,
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'CorrectPass123!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'このアカウントは現在利用できません',
            ]);
        $this->assertGuest();
    }

    /**
     * 指定ロールでログイン後、共通ダッシュボードからロール別トップへ遷移することを確認する。
     */
    private function assertRoleLogin(UserRole $role, string $dashboardRoute): void
    {
        $user = User::factory()->create([
            'email' => $role->value.'@example.test',
            'password' => Hash::make('CorrectPass123!'),
            'role' => $role,
            'status' => UserStatus::Active,
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'CorrectPass123!',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))
            ->assertRedirectToRoute($dashboardRoute);
    }
}
