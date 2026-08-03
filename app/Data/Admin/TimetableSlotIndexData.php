<?php

namespace App\Data\Admin;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\TimetableSlot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * 時間割一覧画面へ渡す表示データを保持する。
 */
final readonly class TimetableSlotIndexData
{
    /**
     * 時間割一覧画面の表示データを生成する。
     *
     * @param LengthAwarePaginator<int, TimetableSlot> $timetableSlots 時間割一覧
     * @param Collection<string, Collection<int, TimetableSlot>> $weeklySlots 週間表示用の時間割
     * @param Collection<int, int> $academicYears 年度選択肢
     * @param list<Grade> $grades 学年選択肢
     * @param EloquentCollection<int, ClassGroup> $classGroups クラス選択肢
     * @param list<DayOfWeek> $daysOfWeek 曜日選択肢
     * @param list<int> $periods 時限選択肢
     * @param list<MasterStatus> $statuses 状態選択肢
     * @param string $keyword キーワード
     * @param int|null $selectedAcademicYear 選択中の年度
     * @param Grade|null $selectedGrade 選択中の学年
     * @param int|null $selectedClassGroupId 選択中のクラスID
     * @param DayOfWeek|null $selectedDayOfWeek 選択中の曜日
     * @param MasterStatus|null $selectedStatus 選択中の状態
     */
    public function __construct(
        public LengthAwarePaginator $timetableSlots,
        public Collection $weeklySlots,
        public Collection $academicYears,
        public array $grades,
        public EloquentCollection $classGroups,
        public array $daysOfWeek,
        public array $periods,
        public array $statuses,
        public string $keyword,
        public ?int $selectedAcademicYear,
        public ?Grade $selectedGrade,
        public ?int $selectedClassGroupId,
        public ?DayOfWeek $selectedDayOfWeek,
        public ?MasterStatus $selectedStatus,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 時間割一覧画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            'timetableSlots' => $this->timetableSlots,
            'weeklySlots' => $this->weeklySlots,
            'showWeeklyGrid' => $this->selectedAcademicYear !== null
                && $this->selectedGrade !== null
                && $this->selectedClassGroupId !== null,
            'academicYears' => $this->academicYears,
            'grades' => $this->grades,
            'classGroups' => $this->classGroups,
            'daysOfWeek' => $this->daysOfWeek,
            'periods' => $this->periods,
            'statuses' => $this->statuses,
            'keyword' => $this->keyword,
            'selectedAcademicYear' => $this->selectedAcademicYear,
            'selectedGrade' => $this->selectedGrade?->value,
            'selectedClassGroupId' => $this->selectedClassGroupId,
            'selectedDayOfWeek' => $this->selectedDayOfWeek?->value,
            'selectedStatus' => $this->selectedStatus?->value,
        ];
    }
}
