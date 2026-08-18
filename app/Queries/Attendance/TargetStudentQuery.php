<?php

namespace App\Queries\Attendance;

use App\Enums\StudentStatus;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class TargetStudentQuery
{
    /**
     * 授業の学年・クラスに一致する在籍生徒を取得する。
     *
     * @param  Course  $course  対象授業
     * @return EloquentCollection<int, Student>
     */
    public function execute(Course $course): EloquentCollection
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
     * 複数授業の対象生徒数を、学年・クラス単位でまとめて取得する。
     *
     * @param  Collection<int, Course>  $courses  対象授業
     * @return array<int, int> 授業IDごとの対象生徒数
     */
    public function counts(Collection $courses): array
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

        $countsByTarget = Student::query()
            ->select([
                'grade',
                'class_group_id',
            ])
            ->selectRaw('COUNT(*) AS aggregate')
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
            ->groupBy([
                'grade',
                'class_group_id',
            ])
            ->get()
            ->mapWithKeys(
                static fn (Student $student): array => [
                    sprintf(
                        '%s:%d',
                        $student->grade->value,
                        $student->class_group_id,
                    ) => (int) $student->getAttribute('aggregate'),
                ],
            );

        return $courses
            ->mapWithKeys(
                static function (Course $course) use ($countsByTarget): array {
                    $key = sprintf(
                        '%s:%d',
                        $course->grade->value,
                        $course->class_group_id,
                    );

                    return [
                        $course->id => (int) $countsByTarget->get($key, 0),
                    ];
                },
            )
            ->all();
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
