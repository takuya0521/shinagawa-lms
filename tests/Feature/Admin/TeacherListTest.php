<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_teacher_list(): void
    {
        $admin = $this->createAdmin();

        $teacherUser = $this->createTeacherUser(
            name: '一覧表示教員',
            email: 'teacher-list@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $teacherUser->id,
            'subject_notes' => '数学を担当',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index'));

        $response
            ->assertOk()
            ->assertSeeText('一覧表示教員')
            ->assertSeeText('teacher-list@example.com')
            ->assertSeeText('数学を担当');
    }

    public function test_teacher_cannot_view_teacher_list(): void
    {
        $teacher = $this->createTeacherUser(
            name: 'アクセス不可教員',
            email: 'forbidden-teacher@example.com',
        );

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.teachers.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_search_teacher_by_keyword(): void
    {
        $admin = $this->createAdmin();

        $targetUser = $this->createTeacherUser(
            name: '検索対象教員',
            email: 'target-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $targetUser->id,
            'subject_notes' => '英語担当',
        ]);

        $otherUser = $this->createTeacherUser(
            name: '別の教員',
            email: 'other-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $otherUser->id,
            'subject_notes' => '数学担当',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index', [
                'keyword' => '英語',
            ]));

        $response
            ->assertOk()
            ->assertSeeText('検索対象教員')
            ->assertDontSeeText('別の教員');
    }

    public function test_admin_can_filter_teachers_by_teacher_status(): void
    {
        $admin = $this->createAdmin();

        $activeUser = $this->createTeacherUser(
            name: '有効教員',
            email: 'active-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $activeUser->id,
            'status' => MasterStatus::Active,
        ]);

        $inactiveUser = $this->createTeacherUser(
            name: '無効教員',
            email: 'inactive-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $inactiveUser->id,
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index', [
                'teacher_status' => MasterStatus::Inactive->value,
            ]));

        $response
            ->assertOk()
            ->assertSeeText('無効教員')
            ->assertDontSeeText('有効教員');
    }

    public function test_admin_can_filter_teachers_by_account_status(): void
    {
        $admin = $this->createAdmin();

        $activeUser = $this->createTeacherUser(
            name: '利用中教員',
            email: 'available-teacher@example.com',
            status: UserStatus::Active,
        );

        Teacher::factory()->create([
            'user_id' => $activeUser->id,
        ]);

        $suspendedUser = $this->createTeacherUser(
            name: '停止中教員',
            email: 'suspended-teacher@example.com',
            status: UserStatus::Suspended,
        );

        Teacher::factory()->create([
            'user_id' => $suspendedUser->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index', [
                'account_status' => UserStatus::Suspended->value,
            ]));

        $response
            ->assertOk()
            ->assertSeeText('停止中教員')
            ->assertDontSeeText('利用中教員');
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }

    private function createTeacherUser(
        string $name,
        string $email,
        UserStatus $status = UserStatus::Active,
    ): User {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => UserRole::Teacher,
            'status' => $status,
        ]);
    }
}
