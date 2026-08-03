<?php

namespace Tests\Feature\Admin;

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

final class EvaluationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_evaluated_and_missing_students(): void
    {
        [$course, $evaluatedStudent, $missingStudent, $teacher] = $this->evaluationContext();
        FinalEvaluation::factory()->create([
            'student_id' => $evaluatedStudent->id,
            'course_id' => $course->id,
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual,
            'evaluated_by' => $teacher->user_id,
            'status' => EvaluationStatus::Draft,
        ]);

        $this
            ->actingAs($this->admin())
            ->get(route('admin.evaluations.index', [
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
            ]))
            ->assertOk()
            ->assertSeeText($evaluatedStudent->student_name)
            ->assertSeeText($missingStudent->student_name)
            ->assertSeeText('未入力');
    }

    public function test_admin_can_filter_missing_evaluations(): void
    {
        [$course, $evaluatedStudent, $missingStudent, $teacher] = $this->evaluationContext();
        FinalEvaluation::factory()->create([
            'student_id' => $evaluatedStudent->id,
            'course_id' => $course->id,
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual,
            'evaluated_by' => $teacher->user_id,
        ]);

        $response = $this
            ->actingAs($this->admin())
            ->get(route('admin.evaluations.index', [
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
                'missing_only' => 1,
            ]));

        $response
            ->assertOk()
            ->assertSee(
                '<p class="font-semibold">'
                    .$missingStudent->student_name
                    .'</p>',
                false,
            )
            ->assertDontSee(
                '<p class="font-semibold">'
                    .$evaluatedStudent->student_name
                    .'</p>',
                false,
            );
    }

    public function test_admin_correction_requires_reason(): void
    {
        $evaluation = FinalEvaluation::factory()->create();

        $this
            ->actingAs($this->admin())
            ->from(route('admin.evaluations.edit', $evaluation))
            ->put(route('admin.evaluations.update', $evaluation), [
                'submission_score' => 80,
                'attendance_score' => 90,
                'attitude_score' => 70,
                'correction_reason' => '',
            ])
            ->assertSessionHasErrors('correction_reason');
    }

    public function test_admin_can_correct_evaluation_and_operation_log_is_created(): void
    {
        $this->configureGrading();
        $admin = $this->admin();
        $evaluation = FinalEvaluation::factory()->create([
            'submission_score' => 60,
            'attendance_score' => 60,
            'attitude_score' => 60,
            'total_score' => 60,
            'grade_level' => 3,
            'status' => EvaluationStatus::Confirmed,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.evaluations.update', $evaluation), [
                'submission_score' => 90,
                'attendance_score' => 100,
                'attitude_score' => 80,
                'correction_reason' => '教員から訂正依頼があったため',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('final_evaluations', [
            'id' => $evaluation->id,
            'submission_score' => 90,
            'attendance_score' => 100,
            'attitude_score' => 80,
            'total_score' => 90,
            'grade_level' => 5,
        ]);
        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'evaluation_correct',
            'target_table' => 'final_evaluations',
            'target_id' => $evaluation->id,
        ]);

        $log = $admin->operationLogs()->latest('id')->firstOrFail();
        $this->assertSame(
            '教員から訂正依頼があったため',
            $log->detail['correction_reason'],
        );
        $this->assertSame('60.00', $log->detail['before']['total_score']);
        $this->assertSame('90.00', $log->detail['after']['total_score']);
    }

    public function test_teacher_cannot_access_admin_evaluation_pages(): void
    {
        $teacher = Teacher::factory()->create();
        $evaluation = FinalEvaluation::factory()->create();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.evaluations.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.evaluations.edit', $evaluation))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->put(route('admin.evaluations.update', $evaluation), [
                'submission_score' => 80,
                'attendance_score' => 80,
                'attitude_score' => 80,
                'correction_reason' => '不正な操作',
            ])
            ->assertForbidden();
    }

    /** @return array{0: Course, 1: Student, 2: Student, 3: Teacher} */
    private function evaluationContext(): array
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'academic_year' => 2026,
        ]);
        $evaluatedStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '評価済み生徒',
        ]);
        $missingStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '未入力生徒',
        ]);

        return [$course, $evaluatedStudent, $missingStudent, $teacher];
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }

    private function configureGrading(): void
    {
        config()->set('lms.evaluation.rounding_mode', 'round');
        config()->set('lms.evaluation.grade_thresholds', [
            5 => 80,
            4 => 70,
            3 => 60,
            2 => 40,
            1 => 0,
        ]);
    }
}
