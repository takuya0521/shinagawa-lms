<?php

namespace App\Queries\Teacher;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class AssignedCourseListQuery
{
    /**
     * ログイン教員の担当授業一覧を取得する。
     *
     * @param  Teacher  $teacher  対象教員
     * @param  ?int  $academicYear  対象年度
     * @param  ?Grade  $grade  学年
     * @param  ?int  $classGroupId  対象データの識別子
     * @param  ?int  $subjectId  対象データの識別子
     * @return LengthAwarePaginator<int, Course>
     */
    public function execute(
        Teacher $teacher,
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
        ?int $subjectId,
    ): LengthAwarePaginator {
        return Course::query()
            ->with([
                'subject',
                'classGroup.students' => static function (Relation $relation): void {
                    $relation->getQuery()->where(
                        'status',
                        StudentStatus::Active->value,
                    );
                },
                'timetableSlots' => static function (Relation $relation): void {
                    $relation->getQuery()
                        ->where(
                            'status',
                            MasterStatus::Active->value,
                        )
                        ->orderBy('day_of_week')
                        ->orderBy('period_no');
                },
            ])
            ->where(
                'teacher_id',
                $teacher->id,
            )
            ->where(
                'status',
                MasterStatus::Active->value,
            )
            ->when(
                $academicYear !== null,
                fn (Builder $query): Builder => $query->where(
                    'academic_year',
                    $academicYear,
                ),
            )
            ->when(
                $grade !== null,
                fn (Builder $query): Builder => $query->where(
                    'grade',
                    $grade->value,
                ),
            )
            ->when(
                $classGroupId !== null,
                fn (Builder $query): Builder => $query->where(
                    'class_group_id',
                    $classGroupId,
                ),
            )
            ->when(
                $subjectId !== null,
                fn (Builder $query): Builder => $query->where(
                    'subject_id',
                    $subjectId,
                ),
            )
            ->orderByDesc('academic_year')
            ->orderBy('grade')
            ->orderBy('course_name')
            ->paginate(20)
            ->withQueryString();
    }
}
