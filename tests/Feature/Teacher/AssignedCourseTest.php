<?php

namespace Tests\Feature\Teacher;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssignedCourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_only_own_active_courses(): void
    {
        $teacher = Teacher::factory()->create();
        $otherTeacher = Teacher::factory()->create();

        $ownCourse = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'course_name' => '自分の担当授業',
            'status' => MasterStatus::Active,
        ]);

        $otherCourse = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'course_name' => '他教員の授業',
            'status' => MasterStatus::Active,
        ]);

        $inactiveCourse = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'course_name' => '無効な担当授業',
            'status' => MasterStatus::Inactive,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.courses.index'))
            ->assertOk()
            ->assertSeeText($ownCourse->course_name)
            ->assertDontSeeText($otherCourse->course_name)
            ->assertDontSeeText($inactiveCourse->course_name);
    }

    public function test_course_student_list_contains_only_matching_grade_and_class(): void
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);

        $matchingStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '対象生徒',
        ]);

        $otherGradeStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::Second,
            'student_name' => '別学年生徒',
        ]);

        $otherClassStudent = Student::factory()->create([
            'grade' => Grade::First,
            'student_name' => '別クラス生徒',
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.courses.students.index', $course))
            ->assertOk()
            ->assertSeeText($matchingStudent->student_name)
            ->assertDontSeeText($otherGradeStudent->student_name)
            ->assertDontSeeText($otherClassStudent->student_name);
    }

    public function test_teacher_cannot_view_another_teachers_course_students(): void
    {
        $teacher = Teacher::factory()->create();
        $otherTeacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.courses.students.index', $course))
            ->assertForbidden();
    }
}
