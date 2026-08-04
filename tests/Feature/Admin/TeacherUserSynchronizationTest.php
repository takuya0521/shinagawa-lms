<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ユーザーと教員プロフィールの同期処理を確認するフィーチャーテスト。
 *
 * 教員ロール付与時のプロフィール作成、ロール変更時の論理削除・担当解除、プロフィール置換を検証する。
 */
final class TeacherUserSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 教員ロールのユーザー登録時に教員プロフィールも作成されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.users.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_teacher_profile_is_created_with_teacher_user(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => '新規教員',
                'email' => 'new-teacher@example.com',
                'role' => UserRole::Teacher->value,
                'status' => UserStatus::Active->value,
                'password' => 'Teacher-Password-123!',
                'password_confirmation' => 'Teacher-Password-123!',
            ]);

        $user = User::query()
            ->where('email', 'new-teacher@example.com')
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.users.edit', $user),
        );

        $this->assertDatabaseHas('teachers', [
            'user_id' => $user->id,
            'status' => MasterStatus::Active->value,
            'deleted_at' => null,
        ]);
    }

    /**
     * 既存の教員ロールユーザーへ教員プロフィールを追加できることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_existing_teacher_user_can_receive_teacher_profile(): void
    {
        $admin = $this->createAdmin();

        $teacherUser = User::factory()->create([
            'name' => '既存教員',
            'email' => 'existing-teacher@example.com',
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.users.update', $teacherUser), [
                'name' => '既存教員',
                'email' => 'existing-teacher@example.com',
                'role' => UserRole::Teacher->value,
                'status' => UserStatus::Active->value,
                'password' => null,
                'password_confirmation' => null,
            ]);

        $response->assertRedirect(
            route('admin.users.edit', $teacherUser),
        );

        $this->assertDatabaseHas('teachers', [
            'user_id' => $teacherUser->id,
            'status' => MasterStatus::Active->value,
            'deleted_at' => null,
        ]);
    }

    /**
     * 教員から別ロールへ変更した際に教員プロフィールが論理削除されることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.users.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、対象レコードが論理削除されることを確認する。
     */
    public function test_teacher_profile_is_soft_deleted_when_role_changes(): void
    {
        $admin = $this->createAdmin();

        $teacher = Teacher::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.users.update', $teacher->user), [
                'name' => $teacher->user->name,
                'email' => $teacher->user->email,
                'role' => UserRole::Admin->value,
                'status' => UserStatus::Active->value,
                'password' => null,
                'password_confirmation' => null,
            ]);

        $response->assertRedirect(
            route('admin.users.edit', $teacher->user),
        );

        $this->assertSoftDeleted('teachers', [
            'id' => $teacher->id,
        ]);
    }

    /**
     * 教員から別ロールへ変更した際に担当授業が解除されることを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.users.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_teacher_courses_are_unassigned_when_role_changes(): void
    {
        $admin = $this->createAdmin();

        $teacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.users.update', $teacher->user), [
                'name' => $teacher->user->name,
                'email' => $teacher->user->email,
                'role' => UserRole::Admin->value,
                'status' => UserStatus::Active->value,
                'password' => null,
                'password_confirmation' => null,
            ]);

        $response->assertRedirect(
            route('admin.users.edit', $teacher->user),
        );

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'teacher_id' => null,
        ]);
    }

    /**
     * 生徒から教員へロール変更した際に生徒プロフィールが教員プロフィールへ置き換わることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.users.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、対象レコードが論理削除されることを確認する。
     */
    public function test_student_profile_is_replaced_when_role_changes_to_teacher(): void
    {
        $admin = $this->createAdmin();

        $student = Student::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.users.update', $student->user), [
                'name' => $student->user->name,
                'email' => $student->user->email,
                'role' => UserRole::Teacher->value,
                'status' => UserStatus::Active->value,
                'password' => null,
                'password_confirmation' => null,
            ]);

        $response->assertRedirect(
            route('admin.users.edit', $student->user),
        );

        $this->assertSoftDeleted('students', [
            'id' => $student->id,
        ]);

        $this->assertDatabaseHas('teachers', [
            'user_id' => $student->user_id,
            'status' => MasterStatus::Active->value,
            'deleted_at' => null,
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
