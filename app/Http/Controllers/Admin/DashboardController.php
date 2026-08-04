<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\Dashboard\AdminDashboardQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者ダッシュボードの表示を制御する。
 */
final class DashboardController extends Controller
{
    /**
     * 管理者ダッシュボードを表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  AdminDashboardQuery  $dashboardQuery  管理者ダッシュボード表示データの検索処理
     * @return View 管理者ダッシュボード画面
     */
    public function __invoke(
        Request $request,
        AdminDashboardQuery $dashboardQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $dashboardData = $dashboardQuery->execute(
            now()->startOfDay(),
        );

        return view(
            'dashboard.admin',
            $dashboardData->toViewData(),
        );
    }
}
