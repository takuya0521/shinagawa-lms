<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceIndexRequest;
use App\Queries\Admin\AttendanceIndexDataQuery;
use Illuminate\View\View;

/**
 * 管理者向け出欠一覧画面を制御する。
 */
final class AttendanceController extends Controller
{
    /**
     * 管理者向け出欠管理画面を表示する。
     *
     * @param  AttendanceIndexRequest  $request  検証済みの検索条件
     * @param  AttendanceIndexDataQuery  $attendanceIndexDataQuery  画面表示データの検索処理
     * @return View 出欠一覧画面
     */
    public function index(
        AttendanceIndexRequest $request,
        AttendanceIndexDataQuery $attendanceIndexDataQuery,
    ): View {
        $data = $attendanceIndexDataQuery->execute(
            dateFrom: $request->dateFrom(),
            dateTo: $request->dateTo(),
            studentId: $request->studentId(),
            courseId: $request->courseId(),
            grade: $request->grade(),
            classGroupId: $request->classGroupId(),
            attendanceStatus: $request->attendanceStatus(),
        );

        return view('admin.attendance.index', $data->toViewData());
    }
}
