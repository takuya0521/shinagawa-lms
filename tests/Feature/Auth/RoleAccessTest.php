<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ロール別ダッシュボード遷移とアクセス制御を確認するフィーチャーテスト。
 *
 * 管理者・教員・生徒の遷移先、他ロール画面の拒否、停止アカウントのログアウトを検証する。
 */
final class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が管理者ダッシュボードへ遷移することを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した画面へリダイレクトされることを確認する。
     */
    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('admin.dashboard');
    }

    /**
     * 教員が教員ダッシュボードへ遷移することを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した画面へリダイレクトされることを確認する。
     */
    public function test_teacher_is_redirected_to_teacher_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('teacher.dashboard');
    }

    /**
     * 生徒が生徒ダッシュボードへ遷移することを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した画面へリダイレクトされることを確認する。
     */
    public function test_student_is_redirected_to_student_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('student.dashboard');
    }

    /**
     * 教員が管理者ダッシュボードへアクセスできないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/admin/dashboard');

        $response->assertForbidden();
    }

    /**
     * 利用停止中のユーザーがログアウトされることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した画面へリダイレクトされる、未認証状態になることを確認する。
     */
    public function test_suspended_user_is_logged_out(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Suspended,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }
}
