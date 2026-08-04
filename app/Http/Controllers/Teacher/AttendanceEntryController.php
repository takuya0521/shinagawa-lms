<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Attendance\EnsureLessonSessionAction;
use App\Actions\Attendance\SaveAttendanceBulkAction;
use App\Enums\AttendanceStatus;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AttendanceEntryRequest;
use App\Http\Requests\Teacher\SaveAttendanceRequest;
use App\Models\LessonSession;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Queries\Attendance\TargetStudentQuery;
use App\Services\TeacherContextService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AttendanceEntryController extends Controller
{
    /**
     * 担当授業の出欠登録画面を表示する。
     *
     * @param  AttendanceEntryRequest  $request  HTTPリクエスト
     * @param  TeacherContextService  $teacherContextService  共通サービス
     * @param  EnsureLessonSessionAction  $ensureLessonSessionAction  業務処理
     * @param  TargetStudentQuery  $targetStudentQuery  データ取得処理
     * @return View 表示する画面
     */
    public function edit(
        AttendanceEntryRequest $request,
        TeacherContextService $teacherContextService,
        EnsureLessonSessionAction $ensureLessonSessionAction,
        TargetStudentQuery $targetStudentQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $timetableSlots = TimetableSlot::query()
            ->with([
                'course.subject',
                'course.classGroup',
            ])
            ->where('status', MasterStatus::Active->value)
            ->whereHas(
                'course',
                fn (Builder $query): Builder => $query
                    ->where('teacher_id', $teacher->id)
                    ->where('status', MasterStatus::Active->value),
            )
            ->orderBy('day_of_week')
            ->orderBy('period_no')
            ->get();

        $lessonSession = null;
        $students = collect();
        $attendanceRecords = collect();
        $selectedSlot = null;
        $lessonDate = $request->lessonDate();
        $timetableSlotId = $request->timetableSlotId();

        if ($timetableSlotId !== null && $lessonDate !== null) {
            $selectedSlot = TimetableSlot::query()
                ->with([
                    'course.subject',
                    'course.classGroup',
                ])
                ->findOrFail($timetableSlotId);

            $teacherContextService->assertAssignedSlot(
                $user,
                $selectedSlot,
            );

            $lessonSession = $ensureLessonSessionAction->execute(
                $selectedSlot,
                CarbonImmutable::createFromFormat('Y-m-d', $lessonDate),
                $user,
            );
            $lessonSession->load('attendanceRecords');
            $students = $targetStudentQuery->execute(
                $selectedSlot->course,
            );
            $attendanceRecords = $lessonSession->attendanceRecords
                ->keyBy('student_id');
        }

        return view('teacher.attendance.edit', [
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
     * 担当授業の出欠を一括保存する。
     *
     * @param  SaveAttendanceRequest  $request  HTTPリクエスト
     * @param  LessonSession  $lessonSession  対象授業実施
     * @param  TeacherContextService  $teacherContextService  共通サービス
     * @param  SaveAttendanceBulkAction  $saveAttendanceBulkAction  業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        SaveAttendanceRequest $request,
        LessonSession $lessonSession,
        TeacherContextService $teacherContextService,
        SaveAttendanceBulkAction $saveAttendanceBulkAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $lessonSession->load('timetableSlot.course');
        $teacherContextService->assertAssignedSlot(
            $user,
            $lessonSession->timetableSlot,
        );

        $result = $saveAttendanceBulkAction->execute(
            $lessonSession,
            $request->records(),
            $user,
            false,
        );

        return redirect()
            ->route('teacher.attendance.edit', [
                'timetable_slot_id' => $lessonSession->timetable_slot_id,
                'lesson_date' => $lessonSession->lesson_date->format('Y-m-d'),
            ])
            ->with(
                'status',
                "出欠を{$result->savedCount}件保存しました。",
            );
    }
}
