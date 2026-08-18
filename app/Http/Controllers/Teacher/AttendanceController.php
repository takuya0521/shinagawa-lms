<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AttendanceIndexRequest;
use App\Models\Course;
use App\Models\LessonSession;
use App\Models\User;
use App\Queries\Teacher\AttendanceRecordListQuery;
use App\Queries\Teacher\AttendanceSessionListQuery;
use App\Services\TeacherContextService;
use Illuminate\View\View;

final class AttendanceController extends Controller
{
    /**
     * 担当授業別の出欠履歴と未登録件数を表示する。
     *
     * @param  AttendanceIndexRequest  $request  HTTPリクエスト
     * @param  AttendanceRecordListQuery  $attendanceRecordListQuery  データ取得処理
     * @param  AttendanceSessionListQuery  $attendanceSessionListQuery  データ取得処理
     * @param  TeacherContextService  $teacherContextService  共通サービス
     * @return View 表示する画面
     */
    public function index(
        AttendanceIndexRequest $request,
        AttendanceRecordListQuery $attendanceRecordListQuery,
        AttendanceSessionListQuery $attendanceSessionListQuery,
        TeacherContextService $teacherContextService,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $courses = Course::query()
            ->with('subject')
            ->where('teacher_id', $teacher->id)
            ->where('status', MasterStatus::Active->value)
            ->orderByDesc('academic_year')
            ->orderBy('course_name')
            ->get();

        $sessionSummaries = $attendanceSessionListQuery->summaries(
            $teacher,
            $request->dateFrom(),
            $request->dateTo(),
            $request->courseId(),
        );

        $missingSessionIds = array_values(
            $sessionSummaries
                ->filter(
                    static fn (LessonSession $lessonSession): bool => (int) $lessonSession
                        ->getAttribute('missing_count') > 0,
                )
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
        );

        $missingCount = $sessionSummaries->sum(
            static fn (LessonSession $lessonSession): int => (int) $lessonSession->getAttribute('missing_count'),
        );

        return view('teacher.attendance.index', [
            'attendanceRecords' => $attendanceRecordListQuery->execute(
                $teacher,
                $request->dateFrom(),
                $request->dateTo(),
                $request->courseId(),
                $request->attendanceStatus(),
                $request->missingOnly()
                    ? $missingSessionIds
                    : null,
            ),
            'courses' => $courses,
            'attendanceStatuses' => AttendanceStatus::cases(),
            'dateFrom' => $request->dateFrom(),
            'dateTo' => $request->dateTo(),
            'selectedCourseId' => $request->courseId(),
            'selectedAttendanceStatus' => $request->attendanceStatus(),
            'missingOnly' => $request->missingOnly(),
            'missingCount' => $missingCount,
            'lessonSessionCount' => $sessionSummaries->count(),
        ]);
    }
}
