<?php

namespace App\Queries\Teacher;

use App\Enums\StudentStatus;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class CourseStudentListQuery
{
    /**
     * 授業の対象となる生徒一覧を取得する。
     *
     * @param  Course  $course  対象授業
     * @param  ?string  $keyword  検索キーワード
     * @param  ?StudentStatus  $status  設定する状態
     * @return LengthAwarePaginator<int, Student>
     */
    public function execute(
        Course $course,
        ?string $keyword,
        ?StudentStatus $status,
    ): LengthAwarePaginator {
        return Student::query()
            ->with([
                'user',
                'classGroup',
            ])
            ->where(
                'class_group_id',
                $course->class_group_id,
            )
            ->where(
                'grade',
                $course->grade->value,
            )
            ->when(
                $keyword !== null,
                function (Builder $query) use ($keyword): void {
                    $query->where(
                        function (Builder $keywordQuery) use ($keyword): void {
                            $keywordQuery
                                ->whereLike(
                                    'student_no',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereLike(
                                    'student_name',
                                    '%'.$keyword.'%',
                                );
                        },
                    );
                },
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where(
                    'status',
                    $status->value,
                ),
                fn (Builder $query): Builder => $query->where(
                    'status',
                    StudentStatus::Active->value,
                ),
            )
            ->orderByRaw('student_no IS NULL')
            ->orderBy('student_no')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();
    }
}
