<?php

namespace App\Queries\Admin;

use App\Enums\DayOfWeek;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Database\Eloquent\Builder;

final class TimetableConflictQuery
{
    /**
     * 同じ授業・曜日・時限の時間割枠を取得する。
     *
     * @param Course $course 対象授業
     * @param DayOfWeek $dayOfWeek 曜日
     * @param int $periodNo 時限番号
     * @param ?int $ignoreTimetableSlotId 対象データの識別子
     * @return ?TimetableSlot 処理結果。取得できない場合はnull
     */
    public function findCourseConflict(
        Course $course,
        DayOfWeek $dayOfWeek,
        int $periodNo,
        ?int $ignoreTimetableSlotId = null,
    ): ?TimetableSlot {
        return $this->baseConflictQuery(
            $dayOfWeek,
            $periodNo,
            $ignoreTimetableSlotId,
        )
            ->where(
                'course_id',
                $course->id,
            )
            ->first();
    }

    /**
     * 同じ年度・学年・クラス・曜日・時限の有効な時間割枠を取得する。
     *
     * @param Course $course 対象授業
     * @param DayOfWeek $dayOfWeek 曜日
     * @param int $periodNo 時限番号
     * @param ?int $ignoreTimetableSlotId 対象データの識別子
     * @return ?TimetableSlot 処理結果。取得できない場合はnull
     */
    public function findClassConflict(
        Course $course,
        DayOfWeek $dayOfWeek,
        int $periodNo,
        ?int $ignoreTimetableSlotId = null,
    ): ?TimetableSlot {
        return $this->baseConflictQuery(
            $dayOfWeek,
            $periodNo,
            $ignoreTimetableSlotId,
        )
            ->where(
                'status',
                MasterStatus::Active->value,
            )
            ->whereHas(
                'course',
                static function (
                    Builder $courseQuery,
                ) use ($course): void {
                    $courseQuery
                        ->where(
                            'academic_year',
                            $course->academic_year,
                        )
                        ->where(
                            'grade',
                            $course->grade->value,
                        )
                        ->where(
                            'class_group_id',
                            $course->class_group_id,
                        );
                },
            )
            ->with([
                'course.subject',
                'course.classGroup',
            ])
            ->first();
    }

    /**
     * 同じ年度・担当教員・曜日・時限の有効な時間割枠を取得する。
     *
     * @param Course $course 対象授業
     * @param DayOfWeek $dayOfWeek 曜日
     * @param int $periodNo 時限番号
     * @param ?int $ignoreTimetableSlotId 対象データの識別子
     * @return ?TimetableSlot 処理結果。取得できない場合はnull
     */
    public function findTeacherConflict(
        Course $course,
        DayOfWeek $dayOfWeek,
        int $periodNo,
        ?int $ignoreTimetableSlotId = null,
    ): ?TimetableSlot {
        if ($course->teacher_id === null) {
            return null;
        }

        return $this->baseConflictQuery(
            $dayOfWeek,
            $periodNo,
            $ignoreTimetableSlotId,
        )
            ->where(
                'status',
                MasterStatus::Active->value,
            )
            ->whereHas(
                'course',
                static function (
                    Builder $courseQuery,
                ) use ($course): void {
                    $courseQuery
                        ->where(
                            'academic_year',
                            $course->academic_year,
                        )
                        ->where(
                            'teacher_id',
                            $course->teacher_id,
                        );
                },
            )
            ->with([
                'course.subject',
                'course.classGroup',
                'course.teacher.user',
            ])
            ->first();
    }

    /**
     * 競合判定対象となる授業と教員を排他ロックする。
     *
     * @param  array<int, Course>  $courses
     *
     * @return void 戻り値なし
     */
    public function lockConflictScopes(
        array $courses,
    ): void {
        $targets = collect($courses)
            ->map(
                static fn (Course $course): array => [
                    'academic_year' => $course->academic_year,
                    'grade' => $course->grade->value,
                    'class_group_id' => $course->class_group_id,
                ],
            )
            ->unique(
                static fn (array $target): string => implode(
                    ':',
                    $target,
                ),
            )
            ->values()
            ->all();

        Course::query()
            ->where(
                static function (
                    Builder $targetQuery,
                ) use ($targets): void {
                    foreach ($targets as $index => $target) {
                        $callback = static function (
                            Builder $courseQuery,
                        ) use ($target): void {
                            $courseQuery
                                ->where(
                                    'academic_year',
                                    $target['academic_year'],
                                )
                                ->where(
                                    'grade',
                                    $target['grade'],
                                )
                                ->where(
                                    'class_group_id',
                                    $target['class_group_id'],
                                );
                        };

                        if ($index === 0) {
                            $targetQuery->where($callback);

                            continue;
                        }

                        $targetQuery->orWhere($callback);
                    }
                },
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get([
                'id',
            ]);

        $teacherIds = collect($courses)
            ->pluck('teacher_id')
            ->filter(
                static fn (mixed $teacherId): bool => $teacherId !== null,
            )
            ->map(
                static fn (mixed $teacherId): int => (int) $teacherId,
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($teacherIds === []) {
            return;
        }

        Teacher::query()
            ->whereKey($teacherIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get([
                'id',
            ]);
    }

    /**
     * 曜日・時限を指定した競合判定の共通クエリを返す。
     *
     * @param DayOfWeek $dayOfWeek 曜日
     * @param int $periodNo 時限番号
     * @param ?int $ignoreTimetableSlotId 対象データの識別子
     * @return Builder<TimetableSlot>
     */
    private function baseConflictQuery(
        DayOfWeek $dayOfWeek,
        int $periodNo,
        ?int $ignoreTimetableSlotId,
    ): Builder {
        return TimetableSlot::query()
            ->where(
                'day_of_week',
                $dayOfWeek->value,
            )
            ->where(
                'period_no',
                $periodNo,
            )
            ->when(
                $ignoreTimetableSlotId !== null,
                static function (
                    Builder $query,
                ) use ($ignoreTimetableSlotId): void {
                    $query->whereKeyNot(
                        $ignoreTimetableSlotId,
                    );
                },
            );
    }
}
