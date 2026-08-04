<?php

namespace App\Queries\Admin;

use App\Enums\EvaluationStatus;
use App\Enums\Grade;
use App\Enums\LessonStatus;
use App\Enums\MasterStatus;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class CourseTeacherAssignmentListQuery
{
    /**
     * 担当講師設定画面へ表示する授業一覧を取得する。
     *
     * @param  string  $keyword  検索キーワード
     * @param  ?int  $academicYear  対象年度
     * @param  ?Grade  $grade  学年
     * @param  ?int  $classGroupId  対象データの識別子
     * @param  ?int  $subjectId  対象データの識別子
     * @param  ?string  $assignmentStatus  担当設定状態
     * @return LengthAwarePaginator<int, Course>
     */
    public function execute(
        string $keyword,
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
        ?int $subjectId,
        ?string $assignmentStatus,
    ): LengthAwarePaginator {
        return Course::query()
            ->with([
                'subject',
                'classGroup',
                'teacher.user',
            ])
            ->withCount([
                'lessonSessions as pending_lesson_sessions_count' => static function (
                    Builder $query,
                ): void {
                    // hasManyThroughで時間割枠を結合するため、statusの所属テーブルを明示する。
                    $query->where(
                        'lesson_sessions.status',
                        LessonStatus::Scheduled->value,
                    );
                },
                'finalEvaluations as draft_evaluations_count' => static function (
                    Builder $query,
                ): void {
                    $query->where(
                        'final_evaluations.status',
                        EvaluationStatus::Draft->value,
                    );
                },
            ])
            ->when(
                $keyword !== '',
                static function (
                    Builder $query,
                ) use ($keyword): void {
                    $query->where(
                        static function (
                            Builder $keywordQuery,
                        ) use ($keyword): void {
                            $keywordQuery
                                ->whereLike(
                                    'courses.course_name',
                                    "%{$keyword}%",
                                )
                                ->orWhereHas(
                                    'subject',
                                    static function (
                                        Builder $subjectQuery,
                                    ) use ($keyword): void {
                                        $subjectQuery
                                            ->whereLike(
                                                'subject_code',
                                                "%{$keyword}%",
                                            )
                                            ->orWhereLike(
                                                'subject_name',
                                                "%{$keyword}%",
                                            );
                                    },
                                )
                                ->orWhereHas(
                                    'teacher.user',
                                    static function (
                                        Builder $userQuery,
                                    ) use ($keyword): void {
                                        $userQuery
                                            ->whereLike(
                                                'name',
                                                "%{$keyword}%",
                                            )
                                            ->orWhereLike(
                                                'email',
                                                "%{$keyword}%",
                                            );
                                    },
                                );
                        },
                    );
                },
            )
            ->when(
                $academicYear !== null,
                static function (
                    Builder $query,
                ) use ($academicYear): void {
                    $query->where(
                        'courses.academic_year',
                        $academicYear,
                    );
                },
            )
            ->when(
                $grade !== null,
                static function (
                    Builder $query,
                ) use ($grade): void {
                    $query->where(
                        'courses.grade',
                        $grade->value,
                    );
                },
            )
            ->when(
                $classGroupId !== null,
                static function (
                    Builder $query,
                ) use ($classGroupId): void {
                    $query->where(
                        'courses.class_group_id',
                        $classGroupId,
                    );
                },
            )
            ->when(
                $subjectId !== null,
                static function (
                    Builder $query,
                ) use ($subjectId): void {
                    $query->where(
                        'courses.subject_id',
                        $subjectId,
                    );
                },
            )
            ->when(
                $assignmentStatus === 'assigned',
                static function (Builder $query): void {
                    $query->whereNotNull(
                        'courses.teacher_id',
                    );
                },
            )
            ->when(
                $assignmentStatus === 'unassigned',
                static function (Builder $query): void {
                    $query->whereNull(
                        'courses.teacher_id',
                    );
                },
            )
            ->orderByRaw(
                'CASE WHEN courses.status = ? THEN 0 ELSE 1 END',
                [MasterStatus::Active->value],
            )
            ->orderBy('courses.grade')
            ->orderBy('courses.class_group_id')
            ->orderBy('courses.subject_id')
            ->orderBy('courses.course_name')
            ->paginate(50)
            ->withQueryString();
    }
}
