<?php

namespace App\Queries\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\Grade;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class AttendanceRecordListQuery
{
    /**
     * 管理者向け出欠記録一覧を取得する。
     *
     * @param  CarbonInterface  $dateFrom  検索開始日
     * @param  CarbonInterface  $dateTo  検索終了日
     * @param  ?int  $studentId  対象生徒ID
     * @param  ?int  $courseId  対象授業ID
     * @param  ?Grade  $grade  学年
     * @param  ?int  $classGroupId  対象データの識別子
     * @param  ?AttendanceStatus  $attendanceStatus  出欠状態
     * @return LengthAwarePaginator<int, AttendanceRecord>
     */
    public function execute(
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $studentId,
        ?int $courseId,
        ?Grade $grade,
        ?int $classGroupId,
        ?AttendanceStatus $attendanceStatus,
    ): LengthAwarePaginator {
        return AttendanceRecord::query()
            ->with([
                'student.classGroup',
                'lessonSession.timetableSlot.course.subject',
                'lessonSession.timetableSlot.course.classGroup',
                'recorder',
                'corrector',
            ])
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
                $studentId !== null,
                fn (Builder $query): Builder => $query->where(
                    'student_id',
                    $studentId,
                ),
            )
            ->when(
                $attendanceStatus !== null,
                fn (Builder $query): Builder => $query->where(
                    'attendance_status',
                    $attendanceStatus->value,
                ),
            )
            ->whereHas(
                'lessonSession.timetableSlot.course',
                function (Builder $query) use (
                    $courseId,
                    $grade,
                    $classGroupId,
                ): void {
                    if ($courseId !== null) {
                        $query->where('id', $courseId);
                    }

                    if ($grade !== null) {
                        $query->where('grade', $grade->value);
                    }

                    if ($classGroupId !== null) {
                        $query->where(
                            'class_group_id',
                            $classGroupId,
                        );
                    }
                },
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
            ->orderBy('student_id')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * KPI集計対象の授業実施日を取得する。
     *
     * @param  CarbonInterface  $dateFrom  検索開始日
     * @param  CarbonInterface  $dateTo  検索終了日
     * @param  ?Student  $student  対象生徒
     * @param  ?int  $courseId  対象授業ID
     * @param  ?Grade  $grade  学年
     * @param  ?int  $classGroupId  対象データの識別子
     * @return Collection<int, LessonSession>
     */
    public function sessionsForStatistics(
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?Student $student,
        ?int $courseId,
        ?Grade $grade,
        ?int $classGroupId,
    ): Collection {
        return LessonSession::query()
            ->with([
                'timetableSlot.course',
                'attendanceRecords' => static function (Relation $relation) use ($student): void {
                    if ($student !== null) {
                        $relation->getQuery()->where(
                            'student_id',
                            $student->id,
                        );
                    }
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
                    $grade,
                    $classGroupId,
                ): void {
                    if ($student !== null) {
                        $query
                            ->where(
                                'grade',
                                $student->grade->value,
                            )
                            ->where(
                                'class_group_id',
                                $student->class_group_id,
                            );
                    }

                    if ($courseId !== null) {
                        $query->where('id', $courseId);
                    }

                    if ($grade !== null) {
                        $query->where('grade', $grade->value);
                    }

                    if ($classGroupId !== null) {
                        $query->where(
                            'class_group_id',
                            $classGroupId,
                        );
                    }
                },
            )
            ->orderBy('lesson_date')
            ->get();
    }
}
