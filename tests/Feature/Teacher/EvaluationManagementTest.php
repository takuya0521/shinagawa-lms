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

/**
 * 教員向け評価管理機能を確認するフィーチャーテスト。
 *
 * 担当授業の評価表示・入力、下書き・確定条件、対象生徒・他教員授業のアクセス制御を検証する。
 */
final class EvaluationManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 教員に自身の担当授業に関する評価だけが表示されることを確認する。
     *
     * 前提: 教員、授業、最終評価など、検証に必要なテストデータを準備する。
     * 処理: `teacher.evaluations.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
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

    /**
     * 教員が担当授業の評価入力画面を開けることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.evaluations.entry`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
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

    /**
     * 評価基準が未確定でも教員が評価を下書き保存できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.evaluations.save`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
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

    /**
     * 評価基準が未確定の場合に教員が評価を確定できないことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.evaluations.save`へPUTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返る、不要なデータがデータベースに保存されないことを確認する。
     */
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

    /**
     * 出欠と評価基準が揃った場合に教員が評価を確定できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.evaluations.save`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
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

    /**
     * 教員が授業対象外の生徒へ評価を保存できないことを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `teacher.evaluations.save`へPUTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
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

    /**
     * 教員が他教員の担当授業に関する評価を管理できないことを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `teacher.evaluations.entry`へGETリクエスト、`teacher.evaluations.save`へPUTリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
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

    /**
     * 評価管理テストに必要な授業、担当教員、生徒、評価データをまとめて作成する。
     */
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

    /**
     * 評価確定条件を満たすため、対象授業の出欠記録を完了状態で作成する。
     */
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

    /**
     * 評価確定テストで使用する出欠・評価基準の設定値を構成する。
     */
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
