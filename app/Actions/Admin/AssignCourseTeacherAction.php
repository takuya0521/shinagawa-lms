<?php

namespace App\Actions\Admin;

use App\Data\Admin\CourseTeacherAssignmentResult;
use App\Enums\EvaluationStatus;
use App\Enums\LessonStatus;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Models\LessonSession;
use App\Models\Teacher;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 授業の担当教員を設定または解除する。
 */
final readonly class AssignCourseTeacherAction
{
    /**
     * 操作ログ記録サービスを受け取る。
     *
     * @param  OperationLogWriter  $operationLogWriter  操作ログ記録サービス
     */
    public function __construct(
        private OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * 授業の担当教員を設定または解除する。
     *
     * @param  Course  $course  担当教員を変更する授業
     * @param  int|null  $teacherId  設定する教員ID。担当解除時はnull
     * @param  User  $actor  操作を実行した管理者
     * @param  string|null  $ipAddress  操作元IPアドレス
     * @return CourseTeacherAssignmentResult 担当教員の変更結果
     *
     * @throws ValidationException 授業または教員を設定できない場合
     */
    public function execute(
        Course $course,
        ?int $teacherId,
        User $actor,
        ?string $ipAddress,
    ): CourseTeacherAssignmentResult {
        return DB::transaction(
            fn (): CourseTeacherAssignmentResult => $this->assign(
                course: $course,
                teacherId: $teacherId,
                actor: $actor,
                ipAddress: $ipAddress,
            ),
        );
    }

    /**
     * ロックした授業へ担当教員の変更を反映する。
     *
     * @param  Course  $course  担当教員を変更する授業
     * @param  int|null  $teacherId  設定する教員ID。担当解除時はnull
     * @param  User  $actor  操作を実行した管理者
     * @param  string|null  $ipAddress  操作元IPアドレス
     * @return CourseTeacherAssignmentResult 担当教員の変更結果
     *
     * @throws ValidationException 授業または教員を設定できない場合
     */
    private function assign(
        Course $course,
        ?int $teacherId,
        User $actor,
        ?string $ipAddress,
    ): CourseTeacherAssignmentResult {
        $lockedCourse = $this->lockCourse($course);
        $beforeTeacherId = $lockedCourse->teacher_id;

        $this->validateCourseChange(
            course: $lockedCourse,
            beforeTeacherId: $beforeTeacherId,
            teacherId: $teacherId,
        );

        if ($beforeTeacherId === $teacherId) {
            return CourseTeacherAssignmentResult::unchanged(
                $this->loadRelations($lockedCourse),
            );
        }

        $this->validateAvailableTeacher($teacherId);

        $pendingLessonSessions = $this->countPendingLessonSessions(
            course: $lockedCourse,
            beforeTeacherId: $beforeTeacherId,
            teacherId: $teacherId,
        );
        $draftEvaluations = $this->countDraftEvaluations(
            course: $lockedCourse,
            beforeTeacherId: $beforeTeacherId,
            teacherId: $teacherId,
        );

        $lockedCourse->update([
            'teacher_id' => $teacherId,
        ]);

        $action = $teacherId === null
            ? 'unassign_course_teacher'
            : 'assign_course_teacher';

        $this->writeOperationLog(
            course: $lockedCourse,
            beforeTeacherId: $beforeTeacherId,
            teacherId: $teacherId,
            actor: $actor,
            action: $action,
            pendingLessonSessions: $pendingLessonSessions,
            draftEvaluations: $draftEvaluations,
            ipAddress: $ipAddress,
        );

        return CourseTeacherAssignmentResult::changed(
            course: $this->loadRelations($lockedCourse->refresh()),
            action: $action,
            pendingLessonSessions: $pendingLessonSessions,
            draftEvaluations: $draftEvaluations,
        );
    }

    /**
     * 更新対象の授業を排他ロックして取得する。
     *
     * @param  Course  $course  更新対象の授業
     * @return Course 排他ロック済みの授業
     */
    private function lockCourse(Course $course): Course
    {
        return Course::query()
            ->whereKey($course->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * 授業状態に対して許可される担当変更かを検証する。
     *
     * @param  Course  $course  更新対象の授業
     * @param  int|null  $beforeTeacherId  変更前の担当教員ID
     * @param  int|null  $teacherId  変更後の担当教員ID
     * @return void 戻り値なし
     *
     * @throws ValidationException 無効な授業へ新しい担当教員を設定する場合
     */
    private function validateCourseChange(
        Course $course,
        ?int $beforeTeacherId,
        ?int $teacherId,
    ): void {
        if (
            $course->status !== MasterStatus::Active
            && $teacherId !== null
            && $teacherId !== $beforeTeacherId
        ) {
            throw ValidationException::withMessages([
                'teacher_id' => '無効な授業には新しい担当教員を設定できません。担当解除のみ可能です。',
            ]);
        }
    }

    /**
     * 選択された教員が担当可能な状態かを検証する。
     *
     * @param  int|null  $teacherId  設定する教員ID。担当解除時はnull
     * @return void 戻り値なし
     *
     * @throws ValidationException 有効な教員として確認できない場合
     */
    private function validateAvailableTeacher(?int $teacherId): void
    {
        if ($teacherId === null) {
            return;
        }

        $availableTeacher = Teacher::query()
            ->whereKey($teacherId)
            ->where('status', MasterStatus::Active->value)
            ->whereHas(
                'user',
                static function (Builder $query): void {
                    $query
                        ->where('role', UserRole::Teacher->value)
                        ->where('status', UserStatus::Active->value);
                },
            )
            ->lockForUpdate()
            ->first();

        if ($availableTeacher === null) {
            throw ValidationException::withMessages([
                'teacher_id' => '選択した担当教員は利用できません。',
            ]);
        }
    }

    /**
     * 担当解除時に残る未完了の授業実施日数を返す。
     *
     * @param  Course  $course  更新対象の授業
     * @param  int|null  $beforeTeacherId  変更前の担当教員ID
     * @param  int|null  $teacherId  変更後の担当教員ID
     * @return int 未完了の授業実施日数。担当解除以外は0
     */
    private function countPendingLessonSessions(
        Course $course,
        ?int $beforeTeacherId,
        ?int $teacherId,
    ): int {
        if (! $this->isUnassignment($beforeTeacherId, $teacherId)) {
            return 0;
        }

        return LessonSession::query()
            ->whereHas(
                'timetableSlot',
                static fn (Builder $query): Builder => $query->where(
                    'course_id',
                    $course->id,
                ),
            )
            ->where('status', LessonStatus::Scheduled->value)
            ->count();
    }

    /**
     * 担当解除時に残る下書き評価数を返す。
     *
     * @param  Course  $course  更新対象の授業
     * @param  int|null  $beforeTeacherId  変更前の担当教員ID
     * @param  int|null  $teacherId  変更後の担当教員ID
     * @return int 下書き評価数。担当解除以外は0
     */
    private function countDraftEvaluations(
        Course $course,
        ?int $beforeTeacherId,
        ?int $teacherId,
    ): int {
        if (! $this->isUnassignment($beforeTeacherId, $teacherId)) {
            return 0;
        }

        return FinalEvaluation::query()
            ->where('course_id', $course->id)
            ->where('status', EvaluationStatus::Draft->value)
            ->count();
    }

    /**
     * 担当教員の解除操作かを判定する。
     *
     * @param  int|null  $beforeTeacherId  変更前の担当教員ID
     * @param  int|null  $teacherId  変更後の担当教員ID
     * @return bool 担当解除の場合はtrue
     */
    private function isUnassignment(
        ?int $beforeTeacherId,
        ?int $teacherId,
    ): bool {
        return $beforeTeacherId !== null && $teacherId === null;
    }

    /**
     * 担当教員変更の操作ログを記録する。
     *
     * @param  Course  $course  更新対象の授業
     * @param  int|null  $beforeTeacherId  変更前の担当教員ID
     * @param  int|null  $teacherId  変更後の担当教員ID
     * @param  User  $actor  操作を実行した管理者
     * @param  string  $action  操作ログへ記録する操作名
     * @param  int  $pendingLessonSessions  未完了の授業実施日数
     * @param  int  $draftEvaluations  下書き評価数
     * @param  string|null  $ipAddress  操作元IPアドレス
     * @return void 戻り値なし
     */
    private function writeOperationLog(
        Course $course,
        ?int $beforeTeacherId,
        ?int $teacherId,
        User $actor,
        string $action,
        int $pendingLessonSessions,
        int $draftEvaluations,
        ?string $ipAddress,
    ): void {
        $this->operationLogWriter->write(
            actor: $actor,
            action: $action,
            target: $course,
            detail: [
                'before' => ['teacher_id' => $beforeTeacherId],
                'after' => ['teacher_id' => $teacherId],
                'course_name' => $course->course_name,
                'academic_year' => $course->academic_year,
                'pending_lesson_sessions' => $pendingLessonSessions,
                'draft_evaluations' => $draftEvaluations,
            ],
            ipAddress: $ipAddress,
        );
    }

    /**
     * 画面表示に必要な授業の関連情報を読み込む。
     *
     * @param  Course  $course  対象の授業
     * @return Course 関連情報を読み込んだ授業
     */
    private function loadRelations(Course $course): Course
    {
        return $course->load([
            'subject',
            'classGroup',
            'teacher.user',
        ]);
    }
}
