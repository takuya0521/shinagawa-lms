<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け教員管理機能を確認するフィーチャーテスト。
 *
 * 登録画面、登録・更新、権限制御を検証する。
 */
final class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が教員登録画面を表示できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.teachers.create`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_teacher_create_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.create'));

        $response
            ->assertOk()
            ->assertSeeText('教員登録')
            ->assertSeeText('担当科目メモ');
    }

    /**
     * 管理者が教員を登録できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.teachers.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_create_teacher(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.teachers.store'), [
                'name' => '登録確認教員',
                'email' => 'teacher-management@example.com',
                'status' => UserStatus::Active->value,
                'password' => 'Teacher-Password-123!',
                'password_confirmation' => 'Teacher-Password-123!',
                'subject_notes' => '英語と英会話を担当',
                'teacher_status' => MasterStatus::Active->value,
            ]);

        $user = User::query()
            ->where('email', 'teacher-management@example.com')
            ->firstOrFail();

        $teacher = $user
            ->teacher()
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.teachers.edit', $teacher),
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::Teacher->value,
            'status' => UserStatus::Active->value,
        ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'user_id' => $user->id,
            'subject_notes' => '英語と英会話を担当',
            'status' => MasterStatus::Active->value,
        ]);
    }

    /**
     * 管理者が教員情報を更新できることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_update_teacher(): void
    {
        $admin = $this->createAdmin();

        $teacher = Teacher::factory()->create([
            'subject_notes' => '更新前の担当科目',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.teachers.update', $teacher), [
                'name' => '更新後教員',
                'email' => 'updated-teacher@example.com',
                'status' => UserStatus::Suspended->value,
                'password' => null,
                'password_confirmation' => null,
                'subject_notes' => '数学と情報を担当',
                'teacher_status' => MasterStatus::Inactive->value,
            ]);

        $response->assertRedirect(
            route('admin.teachers.edit', $teacher),
        );

        $this->assertDatabaseHas('users', [
            'id' => $teacher->user_id,
            'name' => '更新後教員',
            'email' => 'updated-teacher@example.com',
            'role' => UserRole::Teacher->value,
            'status' => UserStatus::Suspended->value,
        ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'subject_notes' => '数学と情報を担当',
            'status' => MasterStatus::Inactive->value,
        ]);
    }

    /**
     * 教員が教員管理機能を利用できないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.create`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_manage_teachers(): void
    {
        $teacherUser = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.teachers.create'));

        $response->assertForbidden();
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
