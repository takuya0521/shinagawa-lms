<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\EnsureLessonSessionAction;
use App\Actions\Attendance\SaveAttendanceBulkAction;
use App\Enums\AttendanceStatus;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceEntryRequest;
use App\Http\Requests\Admin\SaveAttendanceRequest;
use App\Models\LessonSession;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Queries\Attendance\TargetStudentQuery;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AttendanceEntryController extends Controller
{
    /**
     * 日別出欠確認・修正画面を表示する。
     *
     * @param AttendanceEntryRequest $request HTTPリクエスト
     * @param EnsureLessonSessionAction $ensureLessonSessionAction 業務処理
     * @param TargetStudentQuery $targetStudentQuery データ取得処理
     * @return View 表示する画面
     */
    public function edit(
        AttendanceEntryRequest $request,
        EnsureLessonSessionAction $ensureLessonSessionAction,
        TargetStudentQuery $targetStudentQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $timetableSlots = TimetableSlot::query()
            ->with([
                'course.subject',
                'course.classGroup',
                'course.teacher.user',
            ])
            ->where('status', MasterStatus::Active->value)
            ->whereHas(
                'course',
                fn (Builder $query): Builder => $query->where(
                    'status',
                    MasterStatus::Active->value,
                ),
            )
            ->orderBy('day_of_week')
            ->orderBy('period_no')
            ->get();

        $lessonSession = null;
        $students = collect();
        $attendanceRecords = collect();
        $selectedSlot = null;
        $lessonDate = $request->lessonDate();

        if ($request->lessonSessionId() !== null) {
            $lessonSession = LessonSession::query()
                ->with([
                    'timetableSlot.course.subject',
                    'timetableSlot.course.classGroup',
                    'attendanceRecords',
                ])
                ->findOrFail($request->lessonSessionId());
            $selectedSlot = $lessonSession->timetableSlot;
            $lessonDate = $lessonSession->lesson_date->format('Y-m-d');
        } elseif (
            $request->timetableSlotId() !== null
            && $lessonDate !== null
        ) {
            $selectedSlot = TimetableSlot::query()
                ->with([
                    'course.subject',
                    'course.classGroup',
                ])
                ->findOrFail($request->timetableSlotId());

            $lessonSession = $ensureLessonSessionAction->execute(
                $selectedSlot,
                CarbonImmutable::createFromFormat('Y-m-d', $lessonDate),
                $user,
            );
            $lessonSession->load('attendanceRecords');
        }

        if (
            $lessonSession instanceof LessonSession
            && $selectedSlot instanceof TimetableSlot
        ) {
            $students = $targetStudentQuery->execute(
                $selectedSlot->course,
            );
            $attendanceRecords = $lessonSession->attendanceRecords
                ->keyBy('student_id');
        }

        return view('admin.attendance.edit', [
            'timetableSlots' => $timetableSlots,
            'selectedSlot' => $selectedSlot,
            'lessonDate' => $lessonDate,
            'lessonSession' => $lessonSession,
            'students' => $students,
            'attendanceRecords' => $attendanceRecords,
            'attendanceStatuses' => AttendanceStatus::cases(),
        ]);
    }

    /**
     * 管理者による出欠修正を一括保存する。
     *
     * @param SaveAttendanceRequest $request HTTPリクエスト
     * @param LessonSession $lessonSession 対象授業実施
     * @param SaveAttendanceBulkAction $saveAttendanceBulkAction 業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        SaveAttendanceRequest $request,
        LessonSession $lessonSession,
        SaveAttendanceBulkAction $saveAttendanceBulkAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $result = $saveAttendanceBulkAction->execute(
            $lessonSession,
            $request->records(),
            $user,
            true,
        );

        return redirect()
            ->route('admin.attendance.edit', [
                'lesson_session_id' => $lessonSession->id,
            ])
            ->with(
                'status',
                "出欠を{$result->savedCount}件保存しました。",
            );
    }
}
