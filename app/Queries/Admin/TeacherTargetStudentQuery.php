<?php

namespace App\Queries\Admin;

use App\Enums\StudentStatus;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class TeacherTargetStudentQuery
{
    /**
     * 担当授業ごとの対象生徒を返す。
     *
     * @param  Collection<int, Course>  $courses
     * @return array<int, Collection<int, Student>>
     */
    public function execute(Collection $courses): array
    {
        if ($courses->isEmpty()) {
            return [];
        }

        $targets = $courses
            ->map(
                static fn (Course $course): string => sprintf(
                    '%s:%d',
                    $course->grade->value,
                    $course->class_group_id,
                ),
            )
            ->unique()
            ->values();

        $students = Student::query()
            ->where('status', StudentStatus::Active->value)
            ->where(
                static function (Builder $query) use ($targets): void {
                    foreach ($targets as $target) {
                        [$grade, $classGroupId] = explode(':', $target);

                        $query->orWhere(
                            static function (Builder $targetQuery) use (
                                $grade,
                                $classGroupId,
                            ): void {
                                $targetQuery
                                    ->where('grade', $grade)
                                    ->where('class_group_id', (int) $classGroupId);
                            },
                        );
                    }
                },
            )
            ->orderBy('student_no')
            ->orderBy('student_name')
            ->get()
            ->groupBy(
                static fn (Student $student): string => sprintf(
                    '%s:%d',
                    $student->grade->value,
                    $student->class_group_id,
                ),
            );

        $studentsByCourse = [];

        foreach ($courses as $course) {
            $key = sprintf(
                '%s:%d',
                $course->grade->value,
                $course->class_group_id,
            );

            /** @var Collection<int, Student> $targetStudents */
            $targetStudents = $students->get($key, collect());
            $studentsByCourse[$course->id] = $targetStudents;
        }

        return $studentsByCourse;
    }
}
