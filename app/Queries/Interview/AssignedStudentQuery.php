<?php

namespace App\Queries\Interview;

use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class AssignedStudentQuery
{
    /**
     * 教員の有効な担当授業に含まれる在籍生徒のクエリを返す。
     *
     * @param Teacher $teacher 対象教員
     * @return Builder<Student>
     */
    public function builder(Teacher $teacher): Builder
    {
        return Student::query()
            ->where('status', StudentStatus::Active->value)
            ->whereExists(
                static function (QueryBuilder $query) use ($teacher): void {
                    $query
                        ->selectRaw('1')
                        ->from('courses')
                        ->whereColumn(
                            'courses.class_group_id',
                            'students.class_group_id',
                        )
                        ->whereColumn(
                            'courses.grade',
                            'students.grade',
                        )
                        ->where(
                            'courses.teacher_id',
                            $teacher->id,
                        )
                        ->where(
                            'courses.status',
                            MasterStatus::Active->value,
                        )
                        ->whereNull('courses.deleted_at');
                },
            );
    }

    /**
     * 教員が現在担当する生徒を選択肢用に取得する。
     *
     * @param Teacher $teacher 対象教員
     * @return Collection<int, Student>
     */
    public function get(Teacher $teacher): Collection
    {
        return $this->builder($teacher)
            ->with('classGroup')
            ->orderByRaw('student_no IS NULL')
            ->orderBy('student_no')
            ->orderBy('student_name')
            ->get();
    }

    /**
     * 生徒が教員の現在の担当範囲に含まれるか判定する。
     *
     * @param Teacher $teacher 対象教員
     * @param Student $student 対象生徒
     * @return bool 判定結果
     */
    public function contains(
        Teacher $teacher,
        Student $student,
    ): bool {
        return $this->builder($teacher)
            ->whereKey($student->id)
            ->exists();
    }
}
