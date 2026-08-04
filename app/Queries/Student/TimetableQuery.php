<?php

namespace App\Queries\Student;

use App\Enums\MasterStatus;
use App\Models\Student;
use App\Models\TimetableSlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class TimetableQuery
{
    /**
     * 生徒本人の学年・クラスに対応する週間時間割を取得する。
     *
     * @param  Student  $student  対象生徒
     * @param  int  $academicYear  対象年度
     * @return Collection<int, TimetableSlot>
     */
    public function execute(
        Student $student,
        int $academicYear,
    ): Collection {
        return TimetableSlot::query()
            ->with([
                'course.subject',
                'course.teacher.user',
            ])
            ->where(
                'status',
                MasterStatus::Active->value,
            )
            ->whereHas(
                'course',
                static function (Builder $query) use (
                    $student,
                    $academicYear,
                ): void {
                    $query
                        ->where(
                            'academic_year',
                            $academicYear,
                        )
                        ->where(
                            'grade',
                            $student->grade->value,
                        )
                        ->where(
                            'class_group_id',
                            $student->class_group_id,
                        )
                        ->where(
                            'status',
                            MasterStatus::Active->value,
                        );
                },
            )
            ->timetableOrder()
            ->get();
    }
}
