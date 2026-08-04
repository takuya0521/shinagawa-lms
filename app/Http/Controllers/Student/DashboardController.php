<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Queries\Announcement\VisibleAnnouncementQuery;
use App\Queries\ExternalLink\ExternalLinkResolver;
use App\Queries\Student\DashboardSummaryQuery;
use App\Queries\Student\TimetableQuery;
use App\Support\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    /**
     * 生徒マイページを表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  TimetableQuery  $timetableQuery  データ取得処理
     * @param  VisibleAnnouncementQuery  $visibleAnnouncementQuery  データ取得処理
     * @param  ExternalLinkResolver  $externalLinkResolver  外部リンク解決処理
     * @param  DashboardSummaryQuery  $dashboardSummaryQuery  データ取得処理
     * @return View 表示する画面
     */
    public function __invoke(
        Request $request,
        TimetableQuery $timetableQuery,
        VisibleAnnouncementQuery $visibleAnnouncementQuery,
        ExternalLinkResolver $externalLinkResolver,
        DashboardSummaryQuery $dashboardSummaryQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $student = Student::query()
            ->with('classGroup')
            ->where('user_id', $user->id)
            ->first();
        abort_if($student === null, 403);

        $academicYear = AcademicYear::forDate();

        return view('dashboard.student', [
            'student' => $student,
            'academicYear' => $academicYear,
            'timetableSlots' => $timetableQuery->execute(
                $student,
                $academicYear,
            ),
            'announcements' => $visibleAnnouncementQuery->latest($user, 5),
            'externalLinks' => $externalLinkResolver->forStudent($student),
            'dashboardSummary' => $dashboardSummaryQuery->execute(
                $student,
                $academicYear,
            ),
        ]);
    }
}
