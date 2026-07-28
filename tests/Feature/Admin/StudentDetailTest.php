<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_student_detail(): void
    {
        $admin = $this->createAdmin();

        $student = Student::factory()->create([
            'student_no' => 'STU-DETAIL-001',
            'student_name' => '詳細確認生徒',
            'grade' => '2',
            'affiliation' => '品川高等学院',
            'partner_school' => '提携校テスト',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.show', $student));

        $response
            ->assertOk()
            ->assertSeeText('生徒詳細')
            ->assertSeeText('STU-DETAIL-001')
            ->assertSeeText('詳細確認生徒')
            ->assertSeeText('品川高等学院')
            ->assertSeeText('提携校テスト')
            ->assertSeeText($student->user->email)
            ->assertSeeText($student->classGroup->class_name);
    }

    public function test_teacher_cannot_view_student_detail(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $student = Student::factory()->create();

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.students.show', $student));

        $response->assertForbidden();
    }

    public function test_missing_student_returns_not_found(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/students/999999');

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
