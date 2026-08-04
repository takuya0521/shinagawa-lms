<?php

namespace App\Queries\Admin;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\TimetableSlot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class TimetableSlotListQuery
{
    /**
     * 管理画面へ表示する時間割一覧を取得する。
     *
     * @param  string  $keyword  検索キーワード
     * @param  ?int  $academicYear  対象年度
     * @param  ?Grade  $grade  学年
     * @param  ?int  $classGroupId  対象データの識別子
     * @param  ?DayOfWeek  $dayOfWeek  曜日
     * @param  ?MasterStatus  $status  設定する状態
     * @return LengthAwarePaginator<int, TimetableSlot>
     */
    public function execute(
        string $keyword,
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
        ?DayOfWeek $dayOfWeek,
        ?MasterStatus $status,
    ): LengthAwarePaginator {
        return $this->filteredQuery(
            $keyword,
            $academicYear,
            $grade,
            $classGroupId,
            $dayOfWeek,
            $status,
        )
            ->orderByDesc('courses.academic_year')
            ->orderBy('courses.grade')
            ->orderBy('courses.class_group_id')
            ->orderBy('timetable_slots.day_of_week')
            ->orderBy('timetable_slots.period_no')
            ->orderBy('courses.course_name')
            ->select('timetable_slots.*')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * 指定された年度・学年・クラスの週間時間割を取得する。
     *
     * @param  int  $academicYear  対象年度
     * @param  Grade  $grade  学年
     * @param  int  $classGroupId  対象データの識別子
     * @return Collection<int, TimetableSlot>
     */
    public function weekly(
        int $academicYear,
        Grade $grade,
        int $classGroupId,
    ): Collection {
        return $this->filteredQuery(
            '',
            $academicYear,
            $grade,
            $classGroupId,
            null,
            null,
        )
            ->orderBy('timetable_slots.period_no')
            ->orderBy('timetable_slots.day_of_week')
            ->orderBy('courses.course_name')
            ->select('timetable_slots.*')
            ->get();
    }

    /**
     * 時間割の絞り込み条件を適用したクエリを返す。
     *
     * @param  string  $keyword  検索キーワード
     * @param  ?int  $academicYear  対象年度
     * @param  ?Grade  $grade  学年
     * @param  ?int  $classGroupId  対象データの識別子
     * @param  ?DayOfWeek  $dayOfWeek  曜日
     * @param  ?MasterStatus  $status  設定する状態
     * @return Builder<TimetableSlot>
     */
    private function filteredQuery(
        string $keyword,
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
        ?DayOfWeek $dayOfWeek,
        ?MasterStatus $status,
    ): Builder {
        return TimetableSlot::query()
            ->with([
                'course.subject',
                'course.classGroup',
                'course.teacher.user',
            ])
            ->join(
                'courses',
                'courses.id',
                '=',
                'timetable_slots.course_id',
            )
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
                                    'course.subject',
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
                                    'course.teacher.user',
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
                $dayOfWeek !== null,
                static function (
                    Builder $query,
                ) use ($dayOfWeek): void {
                    $query->where(
                        'timetable_slots.day_of_week',
                        $dayOfWeek->value,
                    );
                },
            )
            ->when(
                $status !== null,
                static function (
                    Builder $query,
                ) use ($status): void {
                    $query->where(
                        'timetable_slots.status',
                        $status->value,
                    );
                },
            );
    }
}
