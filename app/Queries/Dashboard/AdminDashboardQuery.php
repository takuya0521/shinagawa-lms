<?php

namespace App\Queries\Dashboard;

use App\Data\Dashboard\AdminDashboardData;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use App\Support\AcademicYear;
use Carbon\CarbonInterface;

/**
 * 管理者ダッシュボードの表示データを取得する。
 */
final readonly class AdminDashboardQuery
{
    /**
     * 必要な検索処理を受け取る。
     *
     * @param TodayLessonSummaryQuery $todayLessonSummaryQuery 当日の授業・出欠集計処理
     * @param PendingEvaluationCountQuery $pendingEvaluationCountQuery 未確定評価数の集計処理
     */
    public function __construct(
        private TodayLessonSummaryQuery $todayLessonSummaryQuery,
        private PendingEvaluationCountQuery $pendingEvaluationCountQuery,
    ) {}

    /**
     * 管理者ダッシュボードの表示データを取得する。
     *
     * @param CarbonInterface $today 基準日
     * @return AdminDashboardData 管理者ダッシュボードの表示データ
     */
    public function execute(CarbonInterface $today): AdminDashboardData
    {
        $academicYear = AcademicYear::forDate($today);
        $todayLessonSummary = $this->todayLessonSummaryQuery->execute(
            today: $today,
            academicYear: $academicYear,
        );
        $courses = Course::query()
            ->active()
            ->where('academic_year', $academicYear)
            ->get();

        return new AdminDashboardData(
            todayLessonSummary: $todayLessonSummary,
            unconfirmedEvaluationCount: $this->pendingEvaluationCountQuery->execute(
                courses: $courses,
                academicYear: $academicYear,
            ),
            suspendedUserCount: User::query()
                ->where('status', UserStatus::Suspended->value)
                ->count(),
            importantAnnouncements: Announcement::query()
                ->with('targets')
                ->publishedAt(now())
                ->where('is_important', true)
                ->orderByDesc('publish_start_at')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        );
    }
}
