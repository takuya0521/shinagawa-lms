<?php

namespace App\Queries\Dashboard;

use App\Data\Dashboard\TeacherDashboardData;
use App\Enums\MasterStatus;
use App\Models\Teacher;
use App\Models\User;
use App\Queries\Announcement\VisibleAnnouncementQuery;
use App\Support\AcademicYear;
use Carbon\CarbonInterface;

/**
 * 教員ダッシュボードの表示データを取得する。
 */
final readonly class TeacherDashboardQuery
{
    /**
     * 必要な検索処理を受け取る。
     *
     * @param TodayLessonSummaryQuery $todayLessonSummaryQuery 当日の授業・出欠集計処理
     * @param PendingEvaluationCountQuery $pendingEvaluationCountQuery 未確定評価数の集計処理
     * @param VisibleAnnouncementQuery $visibleAnnouncementQuery 閲覧可能なお知らせの検索処理
     */
    public function __construct(
        private TodayLessonSummaryQuery $todayLessonSummaryQuery,
        private PendingEvaluationCountQuery $pendingEvaluationCountQuery,
        private VisibleAnnouncementQuery $visibleAnnouncementQuery,
    ) {}

    /**
     * 教員ダッシュボードの表示データを取得する。
     *
     * @param Teacher $teacher ログインユーザーに紐付く教員
     * @param User $user ログインユーザー
     * @param CarbonInterface $today 基準日
     * @return TeacherDashboardData 教員ダッシュボードの表示データ
     */
    public function execute(
        Teacher $teacher,
        User $user,
        CarbonInterface $today,
    ): TeacherDashboardData {
        $academicYear = AcademicYear::forDate($today);
        $assignedCourses = $teacher->courses()
            ->where('status', MasterStatus::Active->value)
            ->where('academic_year', $academicYear)
            ->get();

        return new TeacherDashboardData(
            teacher: $teacher,
            todayLessonSummary: $this->todayLessonSummaryQuery->execute(
                today: $today,
                academicYear: $academicYear,
                teacherId: $teacher->id,
            ),
            assignedCourseCount: $assignedCourses->count(),
            evaluationPendingCount: $this->pendingEvaluationCountQuery->execute(
                courses: $assignedCourses,
                academicYear: $academicYear,
            ),
            announcements: $this->visibleAnnouncementQuery->latest($user, 10),
        );
    }
}
