<?php

namespace App\Data\Dashboard;

use App\Models\Announcement;
use App\Models\Teacher;
use Illuminate\Support\Collection;

/**
 * 教員ダッシュボードへ渡す表示データを保持する。
 */
final readonly class TeacherDashboardData
{
    /**
     * 教員ダッシュボードの表示データを生成する。
     *
     * @param  Teacher  $teacher  ログインユーザーに紐付く教員
     * @param  TodayLessonSummary  $todayLessonSummary  当日の授業・出欠集計
     * @param  int  $assignedCourseCount  担当中の有効な授業数
     * @param  int  $evaluationPendingCount  未確定評価数
     * @param  Collection<int, Announcement>  $announcements  閲覧可能な最新のお知らせ一覧
     */
    public function __construct(
        public Teacher $teacher,
        public TodayLessonSummary $todayLessonSummary,
        public int $assignedCourseCount,
        public int $evaluationPendingCount,
        public Collection $announcements,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 教員ダッシュボードの表示データ
     */
    public function toViewData(): array
    {
        return [
            'teacher' => $this->teacher,
            'todaySlots' => $this->todayLessonSummary->slots,
            'today' => $this->todayLessonSummary->today,
            'academicYear' => $this->todayLessonSummary->academicYear,
            'assignedCourseCount' => $this->assignedCourseCount,
            'unregisteredCount' => $this->todayLessonSummary->unregisteredAttendanceCount,
            'evaluationPendingCount' => $this->evaluationPendingCount,
            'announcements' => $this->announcements,
        ];
    }
}
