<?php

namespace Tests\Feature\Admin;

use App\Enums\EvaluationStatus;
use App\Enums\Grade;
use App\Enums\LessonStatus;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Models\LessonSession;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 授業担当教員設定機能を確認するフィーチャーテスト。
 *
 * 一覧・絞り込み、担当割り当て・解除、警告表示、操作ログ、権限制御を検証する。
 */
final class CourseTeacherAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が授業担当教員設定画面を閲覧できることを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_course_teacher_assignment_page(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::factory()->create();

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'course_name' => '英語コミュニケーション',
            'academic_year' => now()->year,
        ]);

        $inactiveCourse = Course::factory()->create([
            'course_name' => '無効な授業',
            'academic_year' => now()->year,
            'status' => MasterStatus::Inactive,
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.course-teacher-assignments.index',
                ),
            )
            ->assertOk()
            ->assertSeeText('A-022')
            ->assertSeeText('担当教員設定')
            ->assertSeeText($course->course_name)
            ->assertSeeText($teacher->user->name)
            ->assertSeeText($inactiveCourse->course_name)
            ->assertSeeText('無効');
    }

    /**
     * 管理者が担当教員未割当の授業を絞り込めることを確認する。
     *
     * 前提: クラス、科目、授業、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_filter_unassigned_courses(): void
    {
        $admin = $this->admin();
        $classGroup = ClassGroup::factory()->create();
        $subject = Subject::factory()->create();

        $target = Course::factory()->create([
            'academic_year' => 2027,
            'grade' => Grade::Second,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'course_name' => '未設定対象授業',
            'teacher_id' => null,
        ]);

        $teacher = Teacher::factory()->create();

        Course::factory()->create([
            'academic_year' => 2027,
            'grade' => Grade::Second,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'course_name' => '設定済み授業',
            'teacher_id' => $teacher->id,
        ]);

        $this
            ->actingAs($admin)
            ->get(route(
                'admin.course-teacher-assignments.index',
                [
                    'academic_year' => 2027,
                    'grade' => Grade::Second->value,
                    'class_group_id' => $classGroup->id,
                    'subject_id' => $subject->id,
                    'assignment_status' => 'unassigned',
                ],
            ))
            ->assertOk()
            ->assertSeeText($target->course_name)
            ->assertDontSeeText('設定済み授業');
    }

    /**
     * 管理者が有効な教員を授業へ割り当て、操作ログが記録されることを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.update`へPATCHリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_assign_active_teacher_and_operation_log_is_created(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => null,
            'course_name' => '担当設定対象',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => $teacher->id,
                    'filter_academic_year' => $course->academic_year,
                ],
            );

        $response
            ->assertRedirect(route(
                'admin.course-teacher-assignments.index',
                [
                    'academic_year' => $course->academic_year,
                ],
            ))
            ->assertSessionHas(
                'status',
                "「{$course->course_name}」の担当教員を「{$teacher->user->name}」に設定しました。",
            );

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'assign_course_teacher',
            'target_table' => 'courses',
            'target_id' => $course->id,
        ]);
    }

    /**
     * 管理者が担当教員を解除した際に未処理データの警告が表示されることを確認する。
     *
     * 前提: 教員、授業、時間割枠、授業実施、最終評価など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.update`へPATCHリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_unassign_teacher_and_pending_data_warning_is_shown(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'course_name' => '担当解除対象',
        ]);

        $slot = TimetableSlot::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'status' => LessonStatus::Scheduled,
        ]);

        FinalEvaluation::factory()->create([
            'course_id' => $course->id,
            'academic_year' => $course->academic_year,
            'status' => EvaluationStatus::Draft,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => '',
                ],
            );

        $response
            ->assertRedirect(
                route(
                    'admin.course-teacher-assignments.index',
                ),
            )
            ->assertSessionHas(
                'status',
                "「{$course->course_name}」の担当教員を解除しました。",
            )
            ->assertSessionHas(
                'warning',
                '担当解除した授業に未完了の授業実施日が1件、下書き評価が1件あります。後任への引継ぎを確認してください。',
            );

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'teacher_id' => null,
        ]);

        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'unassign_course_teacher',
            'target_table' => 'courses',
            'target_id' => $course->id,
        ]);
    }

    /**
     * 割り当て対象外の教員を授業へ設定できないことを確認する。
     *
     * 前提: 授業、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.update`へPATCHリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_unavailable_teacher_cannot_be_assigned(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create([
            'teacher_id' => null,
        ]);

        $inactiveTeacher = Teacher::factory()->create([
            'status' => MasterStatus::Inactive,
        ]);

        $this
            ->actingAs($admin)
            ->from(
                route(
                    'admin.course-teacher-assignments.index',
                ),
            )
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => $inactiveTeacher->id,
                ],
            )
            ->assertSessionHasErrors('teacher_id');

        $suspendedTeacher = Teacher::factory()->create();
        $suspendedTeacher->user->update([
            'status' => UserStatus::Suspended,
        ]);

        $this
            ->actingAs($admin)
            ->from(
                route(
                    'admin.course-teacher-assignments.index',
                ),
            )
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => $suspendedTeacher->id,
                ],
            )
            ->assertSessionHasErrors('teacher_id');
    }

    /**
     * 無効な授業へ新しく担当教員を割り当てられないことを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.update`へPATCHリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_inactive_course_cannot_be_assigned(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'status' => MasterStatus::Inactive,
            'teacher_id' => null,
        ]);

        $this
            ->actingAs($admin)
            ->from(
                route(
                    'admin.course-teacher-assignments.index',
                ),
            )
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => $teacher->id,
                ],
            )
            ->assertSessionHasErrors('teacher_id');
    }

    /**
     * 無効な授業でも既存の担当教員は解除できることを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.update`へPATCHリクエストを送信する。
     * 期待結果: データベースに期待する内容が保存されることを確認する。
     */
    public function test_inactive_course_can_be_unassigned(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'status' => MasterStatus::Inactive,
            'teacher_id' => $teacher->id,
            'course_name' => '無効授業の担当解除',
        ]);

        $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => '',
                ],
            )
            ->assertSessionHas(
                'status',
                "「{$course->course_name}」の担当教員を解除しました。",
            );

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'teacher_id' => null,
        ]);
    }

    /**
     * 同じ教員を再設定しても重複する操作ログを作成しないことを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.update`へPATCHリクエストを送信する。
     * 期待結果: 関連レコード件数が期待どおりになることを確認する。
     */
    public function test_same_teacher_assignment_does_not_create_duplicate_log(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => $teacher->id,
                ],
            )
            ->assertSessionHas(
                'status',
                '担当教員は変更されていません。',
            );

        $this->assertDatabaseCount(
            'operation_logs',
            0,
        );
    }

    /**
     * 教員が授業担当教員設定機能を利用できないことを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.course-teacher-assignments.index`へGETリクエスト、`admin.course-teacher-assignments.update`へPATCHリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_manage_course_teacher_assignments(): void
    {
        $teacher = Teacher::factory()->create();
        $course = Course::factory()->create();

        $this
            ->actingAs($teacher->user)
            ->get(
                route(
                    'admin.course-teacher-assignments.index',
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->patch(
                route(
                    'admin.course-teacher-assignments.update',
                    $course,
                ),
                [
                    'teacher_id' => $teacher->id,
                ],
            )
            ->assertForbidden();
    }

    /**
     * テストで使用する有効な管理者ユーザーを作成して返す。
     */
    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
