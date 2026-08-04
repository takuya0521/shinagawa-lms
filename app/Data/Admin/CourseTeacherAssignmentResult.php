<?php

namespace App\Data\Admin;

use App\Models\Course;

/**
 * 授業の担当教員変更結果を保持する。
 */
final readonly class CourseTeacherAssignmentResult
{
    /**
     * 変更結果を生成する。
     *
     * @param  Course  $course  変更後の関連情報を読み込んだ授業
     * @param  bool  $changed  担当教員が実際に変更されたか
     * @param  string|null  $action  操作ログへ記録した操作名。未変更時はnull
     * @param  int  $pendingLessonSessions  担当解除時に残っている未完了の授業実施日数
     * @param  int  $draftEvaluations  担当解除時に残っている下書き評価数
     */
    public function __construct(
        public Course $course,
        public bool $changed,
        public ?string $action,
        public int $pendingLessonSessions,
        public int $draftEvaluations,
    ) {}

    /**
     * 担当教員が変更されなかった結果を生成する。
     *
     * @param  Course  $course  関連情報を読み込んだ授業
     * @return self 未変更を表す結果
     */
    public static function unchanged(Course $course): self
    {
        return new self(
            course: $course,
            changed: false,
            action: null,
            pendingLessonSessions: 0,
            draftEvaluations: 0,
        );
    }

    /**
     * 担当教員が変更された結果を生成する。
     *
     * @param  Course  $course  変更後の関連情報を読み込んだ授業
     * @param  string  $action  操作ログへ記録した操作名
     * @param  int  $pendingLessonSessions  担当解除時に残っている未完了の授業実施日数
     * @param  int  $draftEvaluations  担当解除時に残っている下書き評価数
     * @return self 変更済みを表す結果
     */
    public static function changed(
        Course $course,
        string $action,
        int $pendingLessonSessions,
        int $draftEvaluations,
    ): self {
        return new self(
            course: $course,
            changed: true,
            action: $action,
            pendingLessonSessions: $pendingLessonSessions,
            draftEvaluations: $draftEvaluations,
        );
    }

    /**
     * 担当教員の解除結果かを判定する。
     *
     * @return bool 担当解除の場合はtrue
     */
    public function isUnassigned(): bool
    {
        return $this->action === 'unassign_course_teacher';
    }

    /**
     * 担当解除後に引継ぎ確認が必要なデータが残っているかを判定する。
     *
     * @return bool 未完了授業実施日または下書き評価がある場合はtrue
     */
    public function hasPendingWork(): bool
    {
        return $this->pendingLessonSessions > 0
            || $this->draftEvaluations > 0;
    }
}
