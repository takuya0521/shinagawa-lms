<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentAttendanceIndexRequest;
use App\Models\Course;
use App\Models\Student;
use App\Queries\Admin\StudentAttendanceListQuery;
use App\Queries\Attendance\AttendanceStatisticsQuery;
use Illuminate\View\View;

final class StudentAttendanceController extends Controller
{
    /**
     * 生徒別出欠詳細画面を表示する。
     *
     * @param StudentAttendanceIndexRequest $request HTTPリクエスト
     * @param Student $student 対象生徒
     * @param StudentAttendanceListQuery $studentAttendanceListQuery データ取得処理
     * @param AttendanceStatisticsQuery $attendanceStatisticsQuery データ取得処理
     * @return View 表示する画面
     */
    public function show(
        StudentAttendanceIndexRequest $request,
        Student $student,
        StudentAttendanceListQuery $studentAttendanceListQuery,
        AttendanceStatisticsQuery $attendanceStatisticsQuery,
    ): View {
        $student->load([
            'user',
            'classGroup',
        ]);

        $statisticsSessions = $studentAttendanceListQuery->sessionsForStatistics(
            $student,
            $request->dateFrom(),
            $request->dateTo(),
            $request->courseId(),
        );

        return view('admin.attendance.student', [
            'student' => $student,
            'attendanceRecords' => $studentAttendanceListQuery->execute(
                $student,
                $request->dateFrom(),
                $request->dateTo(),
                $request->courseId(),
                $request->attendanceStatus(),
            ),
            'statistics' => $attendanceStatisticsQuery->execute(
                $statisticsSessions,
                $student,
            ),
            'courses' => Course::query()
                ->with('subject')
                ->where('grade', $student->grade->value)
                ->where('class_group_id', $student->class_group_id)
                ->orderByDesc('academic_year')
                ->orderBy('course_name')
                ->get(),
            'attendanceStatuses' => AttendanceStatus::cases(),
            'dateFrom' => $request->dateFrom(),
            'dateTo' => $request->dateTo(),
            'selectedCourseId' => $request->courseId(),
            'selectedAttendanceStatus' => $request->attendanceStatus(),
        ]);
    }
}
