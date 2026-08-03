<?php

namespace App\Queries\Teacher;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Teacher;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class AttendanceRecordListQuery
{
    /**
     * 担当授業に限定した生徒別出欠履歴を取得する。
     *
     * @param  list<int>|null  $lessonSessionIds
     *
     * @param Teacher $teacher 対象教員
     * @param CarbonInterface $dateFrom 検索開始日
     * @param CarbonInterface $dateTo 検索終了日
     * @param ?int $courseId 対象授業ID
     * @param ?AttendanceStatus $attendanceStatus 出欠状態
     * @return LengthAwarePaginator<int, AttendanceRecord>
     */
    public function execute(
        Teacher $teacher,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $courseId,
        ?AttendanceStatus $attendanceStatus,
        ?array $lessonSessionIds,
    ): LengthAwarePaginator {
        return AttendanceRecord::query()
            ->select('attendance_records.*')
            ->join(
                'lesson_sessions',
                'lesson_sessions.id',
                '=',
                'attendance_records.lesson_session_id',
            )
            ->with([
                'student',
                'lessonSession.timetableSlot.course.subject',
                'lessonSession.timetableSlot.course.classGroup',
                'recorder',
                'corrector',
            ])
            ->whereBetween(
                'lesson_sessions.lesson_date',
                [
                    $dateFrom->format('Y-m-d'),
                    $dateTo->format('Y-m-d'),
                ],
            )
            ->whereHas(
                'lessonSession.timetableSlot.course',
                function (Builder $query) use (
                    $teacher,
                    $courseId,
                ): void {
                    $query->where(
                        'teacher_id',
                        $teacher->id,
                    );

                    if ($courseId !== null) {
                        $query->where('id', $courseId);
                    }
                },
            )
            ->when(
                $attendanceStatus !== null,
                fn (Builder $query): Builder => $query->where(
                    'attendance_records.attendance_status',
                    $attendanceStatus->value,
                ),
            )
            ->when(
                $lessonSessionIds !== null,
                function (Builder $query) use ($lessonSessionIds): void {
                    if ($lessonSessionIds === []) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    $query->whereIn(
                        'attendance_records.lesson_session_id',
                        $lessonSessionIds,
                    );
                },
            )
            ->orderByDesc('lesson_sessions.lesson_date')
            ->orderBy('attendance_records.student_id')
            ->paginate(50)
            ->withQueryString();
    }
}
