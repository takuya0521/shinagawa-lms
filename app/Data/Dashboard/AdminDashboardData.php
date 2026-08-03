<?php

namespace App\Data\Dashboard;

use App\Models\Announcement;
use Illuminate\Support\Collection;

/**
 * 管理者ダッシュボードへ渡す表示データを保持する。
 */
final readonly class AdminDashboardData
{
    /**
     * 管理者ダッシュボードの表示データを生成する。
     *
     * @param TodayLessonSummary $todayLessonSummary 当日の授業・出欠集計
     * @param int $unconfirmedEvaluationCount 未確定評価数
     * @param int $suspendedUserCount 利用停止中のユーザー数
     * @param Collection<int, Announcement> $importantAnnouncements 重要なお知らせ一覧
     */
    public function __construct(
        public TodayLessonSummary $todayLessonSummary,
        public int $unconfirmedEvaluationCount,
        public int $suspendedUserCount,
        public Collection $importantAnnouncements,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 管理者ダッシュボードの表示データ
     */
    public function toViewData(): array
    {
        return [
            'today' => $this->todayLessonSummary->today,
            'academicYear' => $this->todayLessonSummary->academicYear,
            'todaySlots' => $this->todayLessonSummary->slots,
            'todayLessonCount' => $this->todayLessonSummary->lessonCount,
            'unregisteredAttendanceCount' => $this->todayLessonSummary->unregisteredAttendanceCount,
            'unconfirmedEvaluationCount' => $this->unconfirmedEvaluationCount,
            'suspendedUserCount' => $this->suspendedUserCount,
            'importantAnnouncements' => $this->importantAnnouncements,
        ];
    }
}
