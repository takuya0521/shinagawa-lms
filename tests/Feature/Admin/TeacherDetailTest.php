<?php

namespace Tests\Feature\Admin;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
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

    public function test_teacher_detail_shows_assigned_courses_and_target_students(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'course_name' => '担当授業A',
            'academic_year' => 2026,
        ]);

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 2,
        ]);

        $targetStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '対象生徒A',
        ]);

        Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::Second,
            'student_name' => '対象外生徒B',
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.teachers.show',
                    $teacher,
                ),
            )
            ->assertOk()
            ->assertSeeText('担当授業・対象生徒')
            ->assertSeeText($course->course_name)
            ->assertSeeText('月曜 2時限')
            ->assertSeeText($targetStudent->student_name)
            ->assertDontSeeText('対象外生徒B')
            ->assertSeeText('担当教員設定を開く');
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
