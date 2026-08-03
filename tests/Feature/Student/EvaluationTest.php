<?php

namespace Tests\Feature\Student;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_only_own_confirmed_evaluations(): void
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'academic_year' => 2026,
            'course_name' => '公開済み授業',
        ]);
        $draftCourse = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'academic_year' => 2026,
            'course_name' => '下書き授業',
        ]);
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $otherStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);

        FinalEvaluation::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual,
            'evaluated_by' => $teacher->user_id,
            'status' => EvaluationStatus::Confirmed,
            'grade_level' => 4,
        ]);
        FinalEvaluation::factory()->create([
            'student_id' => $student->id,
            'course_id' => $draftCourse->id,
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual,
            'evaluated_by' => $teacher->user_id,
            'status' => EvaluationStatus::Draft,
        ]);
        FinalEvaluation::factory()->create([
            'student_id' => $otherStudent->id,
            'course_id' => $course->id,
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual,
            'evaluated_by' => $teacher->user_id,
            'status' => EvaluationStatus::Confirmed,
        ]);

        $this
            ->actingAs($student->user)
            ->get(route('student.evaluations.index', [
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
            ]))
            ->assertOk()
            ->assertSeeText($course->course_name)
            ->assertDontSeeText($draftCourse->course_name)
            ->assertSeeText('4');
    }

    public function test_student_without_student_profile_cannot_view_evaluations(): void
    {
        $studentUser = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $this
            ->actingAs($studentUser)
            ->get(route('student.evaluations.index'))
            ->assertForbidden();
    }
}
