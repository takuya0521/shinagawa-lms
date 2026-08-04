<?php

namespace App\Queries\Admin;

use App\Data\Admin\TimetableSlotFormData;
use App\Enums\DayOfWeek;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\TimetableSlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * 時間割登録・編集画面の選択肢と初期値を取得する。
 */
final class TimetableSlotFormDataQuery
{
    /**
     * 時間割登録・編集画面の表示データを取得する。
     *
     * @param  TimetableSlot|null  $timetableSlot  編集対象の時間割枠。登録時はnull
     * @param  int|null  $preferredCourseId  初期選択する授業ID
     * @param  DayOfWeek|null  $preferredDayOfWeek  初期選択する曜日
     * @param  int|null  $preferredPeriodNo  初期選択する時限
     * @return TimetableSlotFormData 時間割フォームの表示データ
     */
    public function execute(
        ?TimetableSlot $timetableSlot = null,
        ?int $preferredCourseId = null,
        ?DayOfWeek $preferredDayOfWeek = null,
        ?int $preferredPeriodNo = null,
    ): TimetableSlotFormData {
        $currentCourseId = $timetableSlot?->course_id;
        $courses = $this->courses($currentCourseId);
        $selectedCourseId = $currentCourseId;

        if (
            $selectedCourseId === null
            && $preferredCourseId !== null
            && $courses->contains('id', $preferredCourseId)
        ) {
            $selectedCourseId = $preferredCourseId;
        }

        return new TimetableSlotFormData(
            courses: $courses,
            daysOfWeek: DayOfWeek::cases(),
            periods: range(1, 6),
            statuses: MasterStatus::cases(),
            selectedCourseId: $selectedCourseId,
            preferredDayOfWeek: $preferredDayOfWeek,
            preferredPeriodNo: $preferredPeriodNo,
        );
    }

    /**
     * 有効な授業と編集前に選択されていた授業を返す。
     *
     * @param  int|null  $currentCourseId  編集前の授業ID
     * @return Collection<int, Course> 授業選択肢
     */
    private function courses(
        ?int $currentCourseId,
    ): Collection {
        return Course::query()
            ->where(
                static function (Builder $query) use ($currentCourseId): void {
                    $query->where('status', MasterStatus::Active->value);

                    if ($currentCourseId !== null) {
                        $query->orWhere('id', $currentCourseId);
                    }
                },
            )
            ->with([
                'subject',
                'classGroup',
                'teacher.user',
            ])
            ->orderByDesc('academic_year')
            ->orderBy('grade')
            ->orderBy('class_group_id')
            ->orderBy('course_name')
            ->get();
    }
}
