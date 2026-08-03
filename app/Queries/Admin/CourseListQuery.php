<?php

namespace App\Queries\Admin;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class CourseListQuery
{
    /**
     * 管理画面へ表示する授業一覧を取得する。
     *
     * @param string $keyword 検索キーワード
     * @param ?int $academicYear 対象年度
     * @param ?Grade $grade 学年
     * @param ?int $classGroupId 対象データの識別子
     * @param ?MasterStatus $status 設定する状態
     * @return LengthAwarePaginator<int, Course>
     */
    public function execute(
        string $keyword,
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
        ?MasterStatus $status,
    ): LengthAwarePaginator {
        return Course::query()
            ->with([
                'subject',
                'classGroup',
                'teacher.user',
            ])
            ->when(
                $keyword !== '',
                function (
                    Builder $query,
                ) use ($keyword): void {
                    $query->where(
                        function (
                            Builder $keywordQuery,
                        ) use ($keyword): void {
                            $keywordQuery
                                ->where(
                                    'course_name',
                                    'like',
                                    "%{$keyword}%",
                                )
                                ->orWhere(
                                    'google_classroom_id',
                                    'like',
                                    "%{$keyword}%",
                                )
                                ->orWhereHas(
                                    'subject',
                                    function (
                                        Builder $subjectQuery,
                                    ) use ($keyword): void {
                                        $subjectQuery
                                            ->where(
                                                'subject_code',
                                                'like',
                                                "%{$keyword}%",
                                            )
                                            ->orWhere(
                                                'subject_name',
                                                'like',
                                                "%{$keyword}%",
                                            );
                                    },
                                )
                                ->orWhereHas(
                                    'teacher.user',
                                    function (
                                        Builder $userQuery,
                                    ) use ($keyword): void {
                                        $userQuery
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$keyword}%",
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
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
                function (
                    Builder $query,
                ) use ($academicYear): void {
                    $query->where(
                        'academic_year',
                        $academicYear,
                    );
                },
            )
            ->when(
                $grade !== null,
                function (
                    Builder $query,
                ) use ($grade): void {
                    $query->where(
                        'grade',
                        $grade->value,
                    );
                },
            )
            ->when(
                $classGroupId !== null,
                function (
                    Builder $query,
                ) use ($classGroupId): void {
                    $query->where(
                        'class_group_id',
                        $classGroupId,
                    );
                },
            )
            ->when(
                $status !== null,
                function (
                    Builder $query,
                ) use ($status): void {
                    $query->where(
                        'status',
                        $status->value,
                    );
                },
            )
            ->orderByDesc('academic_year')
            ->orderBy('grade')
            ->orderBy('class_group_id')
            ->orderBy('course_name')
            ->paginate(20)
            ->withQueryString();
    }
}
