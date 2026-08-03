<?php

namespace Tests\Feature\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\Grade;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EvaluationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_only_assigned_course_evaluations(): void
    {
        [$teacher, $course, $student] = $this->evaluationContext();
        $otherTeacher = Teacher::factory()->create();
        $otherCourse = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'course_name' => '他教員の授業',
            'academic_year' => 2026,
        ]);

        FinalEvaluation::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual,
            'evaluated_by' => $teacher->user_id,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.evaluations.index', [
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
            ]))
            ->assertOk()
            ->assertSeeText($course->course_name)
            ->assertSeeText($student->student_name)
            ->assertDontSeeText($otherCourse->course_name);
    }

    public function test_teacher_can_open_evaluation_entry_for_assigned_course(): void
    {
        [$teacher, $course, $student] = $this->evaluationContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.evaluations.entry', [
                'course_id' => $course->id,
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
            ]))
            ->assertOk()
            ->assertSeeText('最終評価入力')
            ->assertSeeText($student->student_name);
    }

    public function test_teacher_can_save_draft_when_grading_rule_is_unresolved(): void
    {
        [$teacher, $course, $student] = $this->evaluationContext();

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.evaluations.save'), [
                'course_id' => $course->id,
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
                'status' => EvaluationStatus::Draft->value,
                'evaluations' => [[
                    'student_id' => $student->id,
                    'submission_score' => 80,
                    'attitude_score' => 70,
                ]],
            ])
            ->assertRedirect(route('teacher.evaluations.index', [
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
                'course_id' => $course->id,
            ]));

        $this->assertDatabaseHas('final_evaluations', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual->value,
            'submission_score' => 80,
            'attendance_score' => 0,
            'attitude_score' => 70,
            'status' => EvaluationStatus::Draft->value,
            'evaluated_by' => $teacher->user_id,
        ]);
    }

    public function test_teacher_cannot_confirm_when_grading_rule_is_unresolved(): void
    {
        [$teacher, $course, $student] = $this->evaluationContext();
        $this->createCompletedAttendance($teacher, $course, $student);

        $this
            ->actingAs($teacher->user)
            ->from(route('teacher.evaluations.entry', [
                'course_id' => $course->id,
                'academic_year' => 2026,
            ]))
            ->put(route('teacher.evaluations.save'), [
                'course_id' => $course->id,
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
                'status' => EvaluationStatus::Confirmed->value,
                'evaluations' => [[
                    'student_id' => $student->id,
                    'submission_score' => 80,
                    'attitude_score' => 70,
                ]],
            ])
            ->assertSessionHasErrors('evaluations.0.submission_score');

        $this->assertDatabaseMissing('final_evaluations', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => EvaluationStatus::Confirmed->value,
        ]);
    }

    public function test_teacher_can_confirm_when_attendance_and_grading_rules_are_ready(): void
    {
        $this->configureGrading();
        [$teacher, $course, $student] = $this->evaluationContext();
        $this->createCompletedAttendance($teacher, $course, $student);

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.evaluations.save'), [
                'course_id' => $course->id,
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
                'status' => EvaluationStatus::Confirmed->value,
                'evaluations' => [[
                    'student_id' => $student->id,
                    'submission_score' => 90,
                    'attitude_score' => 80,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('final_evaluations', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_score' => 100,
            'total_score' => 90,
            'grade_level' => 5,
            'status' => EvaluationStatus::Confirmed->value,
        ]);
    }

    public function test_teacher_cannot_save_evaluation_for_non_target_student(): void
    {
        [$teacher, $course] = $this->evaluationContext();
        $otherStudent = Student::factory()->create([
            'grade' => Grade::Second,
        ]);

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.evaluations.save'), [
                'course_id' => $course->id,
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
                'status' => EvaluationStatus::Draft->value,
                'evaluations' => [[
                    'student_id' => $otherStudent->id,
                    'submission_score' => 80,
                    'attitude_score' => 70,
                ]],
            ])
            ->assertSessionHasErrors('evaluations');
    }

    public function test_teacher_cannot_manage_another_teachers_course_evaluations(): void
    {
        $teacher = Teacher::factory()->create();
        [$otherTeacher, $course, $student] = $this->evaluationContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.evaluations.entry', [
                'course_id' => $course->id,
                'academic_year' => 2026,
            ]))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.evaluations.save'), [
                'course_id' => $course->id,
                'academic_year' => 2026,
                'term_name' => EvaluationTerm::Annual->value,
                'status' => EvaluationStatus::Draft->value,
                'evaluations' => [[
                    'student_id' => $student->id,
                    'submission_score' => 80,
                    'attitude_score' => 70,
                ]],
            ])
            ->assertForbidden();

        $this->assertNotSame($teacher->id, $otherTeacher->id);
    }

    /** @return array{0: Teacher, 1: Course, 2: Student} */
    private function evaluationContext(): array
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'academic_year' => 2026,
            'course_name' => '評価対象授業',
        ]);
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '評価対象生徒',
        ]);

        return [$teacher, $course, $student];
    }

    private function createCompletedAttendance(
        Teacher $teacher,
        Course $course,
        Student $student,
    ): void {
        $slot = TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);
        $session = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'status' => LessonStatus::Completed,
            'created_by' => $teacher->user_id,
        ]);

        AttendanceRecord::factory()->create([
            'lesson_session_id' => $session->id,
            'student_id' => $student->id,
            'attendance_status' => AttendanceStatus::Present,
            'recorded_by' => $teacher->user_id,
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
