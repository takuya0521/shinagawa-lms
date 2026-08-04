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

/**
 * 生徒向け評価閲覧機能を確認するフィーチャーテスト。
 *
 * 本人の確定済み評価だけが表示され、プロフィール欠損時は閲覧できないことを検証する。
 */
final class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 生徒に本人の確定済み評価だけが表示されることを確認する。
     *
     * 前提: 教員、クラス、授業、生徒、最終評価など、検証に必要なテストデータを準備する。
     * 処理: `student.evaluations.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
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

    /**
     * 生徒プロフィールがないユーザーは評価を閲覧できないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `student.evaluations.index`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
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
