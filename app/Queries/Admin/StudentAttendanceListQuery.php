<?php

namespace App\Queries\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class StudentAttendanceListQuery
{
    /**
     * 生徒別の出欠履歴を取得する。
     *
     * @param Student $student 対象生徒
     * @param CarbonInterface $dateFrom 検索開始日
     * @param CarbonInterface $dateTo 検索終了日
     * @param ?int $courseId 対象授業ID
     * @param ?AttendanceStatus $attendanceStatus 出欠状態
     * @return LengthAwarePaginator<int, AttendanceRecord>
     */
    public function execute(
        Student $student,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $courseId,
        ?AttendanceStatus $attendanceStatus,
    ): LengthAwarePaginator {
        return AttendanceRecord::query()
            ->with([
                'lessonSession.timetableSlot.course.subject',
                'lessonSession.timetableSlot.course.classGroup',
                'recorder',
                'corrector',
            ])
            ->where(
                'student_id',
                $student->id,
            )
            ->whereHas(
                'lessonSession',
                fn (Builder $query): Builder => $query->whereBetween(
                    'lesson_date',
                    [
                        $dateFrom->format('Y-m-d'),
                        $dateTo->format('Y-m-d'),
                    ],
                ),
            )
            ->when(
                $courseId !== null,
                function (Builder $query) use ($courseId): void {
                    $query->whereHas(
                        'lessonSession.timetableSlot.course',
                        fn (Builder $courseQuery): Builder => $courseQuery->where(
                            'id',
                            $courseId,
                        ),
                    );
                },
            )
            ->when(
                $attendanceStatus !== null,
                fn (Builder $query): Builder => $query->where(
                    'attendance_status',
                    $attendanceStatus->value,
                ),
            )
            ->orderByDesc(
                LessonSession::query()
                    ->select('lesson_date')
                    ->whereColumn(
                        'lesson_sessions.id',
                        'attendance_records.lesson_session_id',
                    )
                    ->limit(1),
            )
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * 生徒別KPI集計対象の授業実施日を取得する。
     *
     * @param Student $student 対象生徒
     * @param CarbonInterface $dateFrom 検索開始日
     * @param CarbonInterface $dateTo 検索終了日
     * @param ?int $courseId 対象授業ID
     * @return Collection<int, LessonSession>
     */
    public function sessionsForStatistics(
        Student $student,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $courseId,
    ): Collection {
        return LessonSession::query()
            ->with([
                'timetableSlot.course',
                'attendanceRecords' => static function (Relation $relation) use ($student): void {
                    $relation->getQuery()->where(
                        'student_id',
                        $student->id,
                    );
                },
            ])
            ->whereBetween(
                'lesson_date',
                [
                    $dateFrom->format('Y-m-d'),
                    $dateTo->format('Y-m-d'),
                ],
            )
            ->where(
                'status',
                '!=',
                LessonStatus::Cancelled->value,
            )
            ->whereHas(
                'timetableSlot.course',
                function (Builder $query) use (
                    $student,
                    $courseId,
                ): void {
                    $query
                        ->where(
                            'grade',
                            $student->grade->value,
                        )
                        ->where(
                            'class_group_id',
                            $student->class_group_id,
                        );

                    if ($courseId !== null) {
                        $query->where('id', $courseId);
                    }
                },
            )
            ->orderBy('lesson_date')
            ->get();
    }
}
