<?php

namespace Tests\Feature\Admin;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ユーザーと生徒プロフィールの同期処理を確認するフィーチャーテスト。
 *
 * 生徒ロール付与時のプロフィール作成、既存ユーザーへの関連付け、ロール変更時の論理削除を検証する。
 */
final class StudentUserSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 生徒ロールのユーザー登録時に生徒プロフィールも作成されることを確認する。
     *
     * 前提: クラスなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_student_profile_is_created_with_student_user(): void
    {
        $admin = $this->createAdmin();
        $classGroup = ClassGroup::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => '新規生徒',
                'email' => 'new-student@example.com',
                'role' => UserRole::Student->value,
                'status' => UserStatus::Active->value,
                'password' => 'Student-Password-123!',
                'password_confirmation' => 'Student-Password-123!',
                'student_no' => 'STU-9001',
                'student_name' => '新規生徒',
                'grade' => '1',
                'affiliation' => '品川高等学院',
                'partner_school' => null,
                'class_group_id' => $classGroup->id,
                'student_status' => StudentStatus::Active->value,
            ]);

        $user = User::query()
            ->where('email', 'new-student@example.com')
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.users.edit', $user),
        );

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'student_no' => 'STU-9001',
            'student_name' => '新規生徒',
            'class_group_id' => $classGroup->id,
            'status' => StudentStatus::Active->value,
        ]);
    }

    /**
     * 既存の生徒ロールユーザーへ生徒プロフィールを追加できることを確認する。
     *
     * 前提: クラス、ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_existing_student_user_can_receive_student_profile(): void
    {
        $admin = $this->createAdmin();
        $classGroup = ClassGroup::factory()->create();

        $studentUser = User::factory()->create([
            'name' => '既存生徒',
            'email' => 'existing-student@example.com',
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.users.update', $studentUser), [
                'name' => '既存生徒',
                'email' => 'existing-student@example.com',
                'role' => UserRole::Student->value,
                'status' => UserStatus::Active->value,
                'password' => null,
                'password_confirmation' => null,
                'student_no' => 'STU-9002',
                'student_name' => '既存生徒',
                'grade' => '2',
                'affiliation' => '品川高等学院',
                'partner_school' => null,
                'class_group_id' => $classGroup->id,
                'student_status' => StudentStatus::Active->value,
            ]);

        $response->assertRedirect(
            route('admin.users.edit', $studentUser),
        );

        $this->assertDatabaseHas('students', [
            'user_id' => $studentUser->id,
            'student_no' => 'STU-9002',
            'student_name' => '既存生徒',
        ]);
    }

    /**
     * 生徒から別ロールへ変更した際に生徒プロフィールが論理削除されることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.users.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、対象レコードが論理削除されることを確認する。
     */
    public function test_student_profile_is_soft_deleted_when_role_changes(): void
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
