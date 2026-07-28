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

final class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_student_create_page(): void
    {
        $admin = $this->createAdmin();

        ClassGroup::factory()->create([
            'class_name' => '登録確認クラス',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.create'));

        $response
            ->assertOk()
            ->assertSeeText('生徒登録')
            ->assertSeeText('登録確認クラス');
    }

    public function test_admin_can_create_student(): void
    {
        $admin = $this->createAdmin();

        $classGroup = ClassGroup::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.students.store'), [
                'name' => '生徒アカウント',
                'email' => 'student-management@example.com',
                'status' => UserStatus::Active->value,
                'password' => 'Student-Password-123!',
                'password_confirmation' => 'Student-Password-123!',
                'student_no' => 'STU-MANAGE-001',
                'student_name' => '生徒管理太郎',
                'grade' => '1',
                'affiliation' => '品川高等学院',
                'partner_school' => '提携校A',
                'class_group_id' => $classGroup->id,
                'student_status' => StudentStatus::Active->value,
            ]);

        $user = User::query()
            ->where('email', 'student-management@example.com')
            ->firstOrFail();

        $student = $user
            ->student()
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.students.edit', $student),
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::Student->value,
            'status' => UserStatus::Active->value,
        ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'user_id' => $user->id,
            'student_no' => 'STU-MANAGE-001',
            'student_name' => '生徒管理太郎',
            'class_group_id' => $classGroup->id,
            'status' => StudentStatus::Active->value,
        ]);
    }

    public function test_admin_can_update_student(): void
    {
        $admin = $this->createAdmin();

        $newClassGroup = ClassGroup::factory()->create();

        $student = Student::factory()->create([
            'student_name' => '更新前生徒',
            'grade' => '1',
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.students.update', $student), [
                'name' => '更新後アカウント',
                'email' => 'updated-student@example.com',
                'status' => UserStatus::Active->value,
                'password' => null,
                'password_confirmation' => null,
                'student_no' => 'STU-UPDATED-001',
                'student_name' => '更新後生徒',
                'grade' => '2',
                'affiliation' => '更新後所属',
                'partner_school' => '更新後提携校',
                'class_group_id' => $newClassGroup->id,
                'student_status' => StudentStatus::Graduated->value,
            ]);

        $response->assertRedirect(
            route('admin.students.edit', $student),
        );

        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'name' => '更新後アカウント',
            'email' => 'updated-student@example.com',
            'role' => UserRole::Student->value,
        ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'student_no' => 'STU-UPDATED-001',
            'student_name' => '更新後生徒',
            'grade' => '2',
            'class_group_id' => $newClassGroup->id,
            'status' => StudentStatus::Graduated->value,
        ]);
    }

    public function test_teacher_cannot_manage_students(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.students.create'));

        $response->assertForbidden();
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
