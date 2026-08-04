<?php

namespace App\Queries\Evaluation;

use App\Enums\AttendanceStatus;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\LessonSession;
use App\Models\Student;
use App\Support\Evaluation\AttendanceScoreResult;
use Illuminate\Database\Eloquent\Collection;

final class AttendanceScoreQuery
{
    /**
     * 対象授業における生徒ごとの出欠点算出情報を返す。
     *
     * 遅刻・早退の換算と未登録の扱いは設計上未決のため、
     * present/absentのみが全件登録されている場合に限り算出する。
     *
     * @param  Collection<int, Student>  $students
     * @param  Course  $course  対象授業
     * @return array<int, AttendanceScoreResult>
     */
    public function execute(
        Course $course,
        Collection $students,
    ): array {
        $studentIds = $students
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $lessonSessions = LessonSession::query()
            ->where('status', LessonStatus::Completed->value)
            ->whereHas(
                'timetableSlot',
                static fn ($query) => $query->where(
                    'course_id',
                    $course->id,
                ),
            )
            ->with([
                'attendanceRecords' => static fn ($query) => $query->whereIn(
                    'student_id',
                    $studentIds,
                ),
            ])
            ->orderBy('lesson_date')
            ->get();

        $lessonCount = $lessonSessions->count();
        $results = [];

        foreach ($students as $student) {
            $records = $lessonSessions
                ->map(
                    static fn (LessonSession $lessonSession): ?AttendanceRecord => $lessonSession
                        ->attendanceRecords
                        ->firstWhere('student_id', $student->id),
                )
                ->filter(
                    static fn (?AttendanceRecord $record): bool => $record instanceof AttendanceRecord,
                );

            $presentCount = $this->countStatus(
                $records,
                AttendanceStatus::Present,
            );
            $absentCount = $this->countStatus(
                $records,
                AttendanceStatus::Absent,
            );
            $lateCount = $this->countStatus(
                $records,
                AttendanceStatus::Late,
            );
            $earlyLeaveCount = $this->countStatus(
                $records,
                AttendanceStatus::EarlyLeave,
            );
            $recordedCount = $records->count();
            $missingCount = max(
                $lessonCount - $recordedCount,
                0,
            );

            $score = null;
            $denominator = $presentCount + $absentCount;

            if (
                $lessonCount > 0
                && $missingCount === 0
                && $lateCount === 0
                && $earlyLeaveCount === 0
                && $denominator > 0
            ) {
                $score = round(
                    $presentCount / $denominator * 100,
                    2,
                );
            }

            $results[$student->id] = new AttendanceScoreResult(
                score: $score,
                lessonCount: $lessonCount,
                recordedCount: $recordedCount,
                presentCount: $presentCount,
                absentCount: $absentCount,
                lateCount: $lateCount,
                earlyLeaveCount: $earlyLeaveCount,
                missingCount: $missingCount,
            );
        }

        return $results;
    }

    /**
     * 指定された出欠区分の件数を返す。
     *
     * @param  \Illuminate\Support\Collection<int, AttendanceRecord>  $records
     * @param  AttendanceStatus  $status  設定する状態
     * @return int 取得した整数
     */
    private function countStatus(
        \Illuminate\Support\Collection $records,
        AttendanceStatus $status,
    ): int {
        return $records
            ->filter(
                static fn (AttendanceRecord $record): bool => $record->attendance_status === $status,
            )
            ->count();
    }
}
