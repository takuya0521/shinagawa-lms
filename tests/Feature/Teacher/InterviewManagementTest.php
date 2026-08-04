<?php

namespace Tests\Feature\Teacher;

use App\Enums\Grade;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\InterviewRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 教員向け面談記録管理機能を確認するフィーチャーテスト。
 *
 * 担当生徒の一覧・登録・更新、担当外・他教員記録の制御、導線、管理者アクセス制御を検証する。
 */
final class InterviewManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 教員に自身の担当生徒に関する面談記録だけが表示されることを確認する。
     *
     * 前提: 面談記録など、検証に必要なテストデータを準備する。
     * 処理: `teacher.interviews.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_teacher_sees_only_own_assigned_student_interviews(): void
    {
        [$teacher, $assignedStudent] = $this->teacherContext();
        [$otherTeacher, $otherStudent] = $this->teacherContext();

        $ownRecord = InterviewRecord::factory()->create([
            'student_id' => $assignedStudent->id,
            'teacher_id' => $teacher->id,
            'created_by' => $teacher->user_id,
            'interview_date' => now()->format('Y-m-d'),
            'interview_type' => '担当生徒面談',
        ]);
        $otherRecord = InterviewRecord::factory()->create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $otherTeacher->id,
            'created_by' => $otherTeacher->user_id,
            'interview_date' => now()->format('Y-m-d'),
            'interview_type' => '他教員面談',
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.interviews.index'))
            ->assertOk()
            ->assertSeeText($ownRecord->student->student_name)
            ->assertSeeText('担当生徒面談')
            ->assertDontSeeText($otherRecord->student->student_name)
            ->assertDontSeeText('他教員面談');
    }

    /**
     * 教員が担当生徒の面談記録を登録できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.interviews.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_teacher_can_create_interview_for_assigned_student(): void
    {
        [$teacher, $assignedStudent] = $this->teacherContext();

        $response = $this
            ->actingAs($teacher->user)
            ->post(route('teacher.interviews.store'), [
                'student_id' => $assignedStudent->id,
                'interview_date' => '2026-07-29',
                'interview_type' => '希望面談',
                'memo' => '相談内容',
                'next_action' => '次回確認',
                'drive_url' => 'https://drive.google.com/example',
                'meet_url' => null,
            ]);

        $interviewRecord = InterviewRecord::query()->firstOrFail();

        $response
            ->assertRedirect(
                route('teacher.interviews.edit', $interviewRecord),
            )
            ->assertSessionHas('success', '面談記録を登録しました。');

        $this->assertDatabaseHas('interview_records', [
            'student_id' => $assignedStudent->id,
            'teacher_id' => $teacher->id,
            'interview_type' => '希望面談',
            'created_by' => $teacher->user_id,
        ]);
    }

    /**
     * 教員が担当外生徒の面談記録を登録できないことを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `teacher.interviews.store`へPOSTリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否される、不要なデータがデータベースに保存されないことを確認する。
     */
    public function test_teacher_cannot_create_interview_for_non_assigned_student(): void
    {
        [$teacher] = $this->teacherContext();
        $otherStudent = Student::factory()->create();

        $this
            ->actingAs($teacher->user)
            ->post(route('teacher.interviews.store'), [
                'student_id' => $otherStudent->id,
                'interview_date' => '2026-07-29',
                'interview_type' => '不正面談',
                'memo' => null,
                'next_action' => null,
                'drive_url' => null,
                'meet_url' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('interview_records', [
            'student_id' => $otherStudent->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    /**
     * 教員が自身で登録した担当生徒の面談記録を更新できることを確認する。
     *
     * 前提: 面談記録など、検証に必要なテストデータを準備する。
     * 処理: `teacher.interviews.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_teacher_can_update_own_assigned_student_interview(): void
    {
        [$teacher, $assignedStudent] = $this->teacherContext();
        $interviewRecord = InterviewRecord::factory()->create([
            'student_id' => $assignedStudent->id,
            'teacher_id' => $teacher->id,
            'created_by' => $teacher->user_id,
            'memo' => '更新前',
        ]);

        $this
            ->actingAs($teacher->user)
            ->put(
                route('teacher.interviews.update', $interviewRecord),
                [
                    'student_id' => $assignedStudent->id,
                    'interview_date' => '2026-07-30',
                    'interview_type' => '進路面談',
                    'memo' => '更新後',
                    'next_action' => '資料を確認',
                    'drive_url' => null,
                    'meet_url' => null,
                ],
            )
            ->assertRedirect(
                route('teacher.interviews.edit', $interviewRecord),
            );

        $this->assertDatabaseHas('interview_records', [
            'id' => $interviewRecord->id,
            'teacher_id' => $teacher->id,
            'memo' => '更新後',
            'updated_by' => $teacher->user_id,
        ]);
    }

    /**
     * 教員が他教員の面談記録を編集できないことを確認する。
     *
     * 前提: 面談記録など、検証に必要なテストデータを準備する。
     * 処理: `teacher.interviews.edit`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_edit_another_teachers_interview(): void
    {
        [$teacher] = $this->teacherContext();
        [$otherTeacher, $otherStudent] = $this->teacherContext();
        $interviewRecord = InterviewRecord::factory()->create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $otherTeacher->id,
            'created_by' => $otherTeacher->user_id,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.interviews.edit', $interviewRecord))
            ->assertForbidden();
    }

    /**
     * 担当授業の生徒一覧に面談登録・履歴表示の操作が用意されていることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.courses.students.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_course_student_list_has_interview_actions(): void
    {
        [$teacher, $assignedStudent, $course] = $this->teacherContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.courses.students.index', $course))
            ->assertOk()
            ->assertSeeText($assignedStudent->student_name)
            ->assertSeeText('面談記録')
            ->assertSeeText('面談履歴');
    }

    /**
     * 管理者が教員向け面談画面へアクセスできないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `teacher.interviews.index`へGETリクエスト、`teacher.interviews.create`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_admin_cannot_access_teacher_interview_pages(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('teacher.interviews.index'))
            ->assertForbidden();

        $this
            ->actingAs($admin)
            ->get(route('teacher.interviews.create'))
            ->assertForbidden();
    }

    /**
     * 教員向け面談テストに必要な教員、担当授業、対象生徒を作成する。
     */
    private function teacherContext(): array
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => fake()->unique()->name(),
        ]);

        return [$teacher, $student, $course];
    }
}
