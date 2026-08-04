<?php

namespace App\Data\Dashboard;

use App\Models\TimetableSlot;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * 当日の授業と出欠登録状況を保持する。
 */
final readonly class TodayLessonSummary
{
    /**
     * 当日の授業集計結果を生成する。
     *
     * @param  CarbonInterface  $today  集計対象日
     * @param  int  $academicYear  集計対象日が属する年度
     * @param  Collection<int, TimetableSlot>  $slots  当日の時間割枠一覧
     * @param  int  $lessonCount  休講を除いた当日の授業数
     * @param  int  $unregisteredAttendanceCount  出欠未登録の生徒がいる授業数
     */
    public function __construct(
        public CarbonInterface $today,
        public int $academicYear,
        public Collection $slots,
        public int $lessonCount,
        public int $unregisteredAttendanceCount,
    ) {}
}
