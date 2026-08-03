<?php

namespace App\Queries\Admin;

use App\Data\Admin\TimetableSlotIndexData;
use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;

/**
 * 時間割一覧画面の表示データを取得する。
 */
final readonly class TimetableSlotIndexDataQuery
{
    /**
     * 時間割一覧検索処理を受け取る。
     *
     * @param TimetableSlotListQuery $timetableSlotListQuery 時間割一覧の検索処理
     */
    public function __construct(
        private TimetableSlotListQuery $timetableSlotListQuery,
    ) {}

    /**
     * 指定された検索条件で時間割一覧画面の表示データを取得する。
     *
     * @param string $keyword キーワード
     * @param int|null $academicYear 年度
     * @param Grade|null $grade 学年
     * @param int|null $classGroupId クラスID
     * @param DayOfWeek|null $dayOfWeek 曜日
     * @param MasterStatus|null $status 状態
     * @return TimetableSlotIndexData 時間割一覧画面の表示データ
     */
    public function execute(
        string $keyword,
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
        ?DayOfWeek $dayOfWeek,
        ?MasterStatus $status,
    ): TimetableSlotIndexData {
        return new TimetableSlotIndexData(
            timetableSlots: $this->timetableSlotListQuery->execute(
                $keyword,
                $academicYear,
                $grade,
                $classGroupId,
                $dayOfWeek,
                $status,
            ),
            weeklySlots: $this->weeklySlots(
                $academicYear,
                $grade,
                $classGroupId,
            ),
            academicYears: $this->academicYears(),
            grades: Grade::cases(),
            classGroups: ClassGroup::query()
                ->orderBy('class_code')
                ->get(),
            daysOfWeek: DayOfWeek::cases(),
            periods: range(1, 6),
            statuses: MasterStatus::cases(),
            keyword: $keyword,
            selectedAcademicYear: $academicYear,
            selectedGrade: $grade,
            selectedClassGroupId: $classGroupId,
            selectedDayOfWeek: $dayOfWeek,
            selectedStatus: $status,
        );
    }

    /**
     * 年度・学年・クラスが指定された場合に週間表示用データを取得する。
     *
     * @param int|null $academicYear 年度
     * @param Grade|null $grade 学年
     * @param int|null $classGroupId クラスID
     * @return Collection<string, Collection<int, TimetableSlot>> 時限・曜日単位でまとめた時間割
     */
    private function weeklySlots(
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
    ): Collection {
        if (
            $academicYear === null
            || $grade === null
            || $classGroupId === null
        ) {
            return collect();
        }

        return $this->timetableSlotListQuery
            ->weekly($academicYear, $grade, $classGroupId)
            ->groupBy(
                static fn (TimetableSlot $slot): string => sprintf(
                    '%d-%d',
                    $slot->period_no,
                    $slot->day_of_week->value,
                ),
            );
    }

    /**
     * DB登録済み年度と現在年度周辺をまとめた年度選択肢を返す。
     *
     * @return Collection<int, int> 降順の年度選択肢
     */
    private function academicYears(): Collection
    {
        return Course::query()
            ->select('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->map(static fn (mixed $year): int => (int) $year)
            ->merge(range(now()->year + 1, now()->year - 1))
            ->unique()
            ->sortDesc()
            ->values();
    }
}
