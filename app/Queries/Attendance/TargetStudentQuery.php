<?php

namespace App\Queries\Attendance;

use App\Enums\StudentStatus;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

final class TargetStudentQuery
{
    /**
     * 授業の学年・クラスに一致する在籍生徒を取得する。
     *
     * @param  Course  $course  対象授業
     * @return Collection<int, Student>
     */
    public function execute(Course $course): Collection
    {
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
            ->where(
                'status',
                StudentStatus::Active->value,
            )
            ->orderByRaw('student_no IS NULL')
            ->orderBy('student_no')
            ->orderBy('id')
            ->get();
    }

    /**
     * 授業の学年・クラスに一致する在籍生徒数を返す。
     *
     * @param  Course  $course  対象授業
     * @return int 取得した整数
     */
    public function count(Course $course): int
    {
        return Student::query()
            ->where(
                'class_group_id',
                $course->class_group_id,
            )
            ->where(
                'grade',
                $course->grade->value,
            )
            ->where(
                'status',
                StudentStatus::Active->value,
            )
            ->count();
    }
}
