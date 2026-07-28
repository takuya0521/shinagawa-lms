<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_teacher_detail(): void
    {
        $admin = $this->createAdmin();

        $teacher = Teacher::factory()->create([
            'subject_notes' => '国語と小論文を担当',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.show', $teacher));

        $response
            ->assertOk()
            ->assertSeeText('教員詳細')
            ->assertSeeText($teacher->user->name)
            ->assertSeeText($teacher->user->email)
            ->assertSeeText('国語と小論文を担当')
            ->assertSeeText('有効');
    }

    public function test_teacher_cannot_view_admin_teacher_detail(): void
    {
        $teacherUser = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $teacher = Teacher::factory()->create();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.teachers.show', $teacher));

        $response->assertForbidden();
    }

    public function test_missing_teacher_returns_not_found(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/teachers/999999');

        $response->assertNotFound();
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
