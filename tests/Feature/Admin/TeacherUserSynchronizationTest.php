<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherUserSynchronizationTest extends TestCase
{
    use RefreshDatabase;

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

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
