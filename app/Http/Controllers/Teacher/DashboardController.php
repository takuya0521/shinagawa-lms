<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\Dashboard\TeacherDashboardQuery;
use App\Services\TeacherContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 教員ダッシュボードの表示を制御する。
 */
final class DashboardController extends Controller
{
    /**
     * 教員ダッシュボードを表示する。
     *
     * @param Request $request HTTPリクエスト
     * @param TeacherContextService $teacherContextService ログインユーザーに紐付く教員を解決するサービス
     * @param TeacherDashboardQuery $dashboardQuery 教員ダッシュボード表示データの検索処理
     * @return View 教員ダッシュボード画面
     */
    public function __invoke(
        Request $request,
        TeacherContextService $teacherContextService,
        TeacherDashboardQuery $dashboardQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $dashboardData = $dashboardQuery->execute(
            teacher: $teacher,
            user: $user,
            today: now()->startOfDay(),
        );

        return view(
            'dashboard.teacher',
            $dashboardData->toViewData(),
        );
    }
}
