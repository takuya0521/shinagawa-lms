<?php

namespace App\Data\Admin;

use App\Enums\DayOfWeek;
use App\Enums\MasterStatus;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;

/**
 * 時間割登録・編集画面へ渡す選択肢と初期値を保持する。
 */
final readonly class TimetableSlotFormData
{
    /**
     * 時間割フォームの表示データを生成する。
     *
     * @param  Collection<int, Course>  $courses  授業選択肢
     * @param  list<DayOfWeek>  $daysOfWeek  曜日選択肢
     * @param  list<int>  $periods  時限選択肢
     * @param  list<MasterStatus>  $statuses  状態選択肢
     * @param  int|null  $selectedCourseId  選択中の授業ID
     * @param  DayOfWeek|null  $preferredDayOfWeek  初期表示する曜日
     * @param  int|null  $preferredPeriodNo  初期表示する時限
     */
    public function __construct(
        public Collection $courses,
        public array $daysOfWeek,
        public array $periods,
        public array $statuses,
        public ?int $selectedCourseId,
        public ?DayOfWeek $preferredDayOfWeek,
        public ?int $preferredPeriodNo,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 時間割フォームの表示データ
     */
    public function toViewData(): array
    {
        return [
            'courses' => $this->courses,
            'daysOfWeek' => $this->daysOfWeek,
            'periods' => $this->periods,
            'statuses' => $this->statuses,
            'selectedCourseId' => $this->selectedCourseId,
            'preferredDayOfWeek' => $this->preferredDayOfWeek,
            'preferredPeriodNo' => $this->preferredPeriodNo,
        ];
    }
}
