<?php

namespace App\Queries\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use App\Support\Attendance\AttendanceStatistics;
use Illuminate\Database\Eloquent\Collection;

final class AttendanceStatisticsQuery
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param TargetStudentQuery $targetStudentQuery データ取得処理
     */
    public function __construct(
        private readonly TargetStudentQuery $targetStudentQuery,
    ) {}

    /**
     * 授業実施日の集合から出欠集計を作成する。
     *
     * 遅刻・早退の換算および未登録の扱いは未決のため、
     * それらが含まれる場合は出席率を確定しない。
     *
     * @param  Collection<int, LessonSession>  $lessonSessions
     *
     * @param ?Student $student 対象生徒
     * @return AttendanceStatistics 処理結果
     */
    public function execute(
        Collection $lessonSessions,
        ?Student $student = null,
    ): AttendanceStatistics {
        $expectedRecordCount = 0;
        $records = new Collection;
        $targetCountCache = [];

        foreach ($lessonSessions as $lessonSession) {
            if ($student !== null) {
                $expectedRecordCount++;

                $studentRecord = $lessonSession->attendanceRecords
                    ->firstWhere(
                        'student_id',
                        $student->id,
                    );

                if ($studentRecord instanceof AttendanceRecord) {
                    $records->push($studentRecord);
                }

                continue;
            }

            $course = $lessonSession->timetableSlot->course;
            $targetKey = $course->class_group_id.'-'.$course->grade->value;

            if (! array_key_exists($targetKey, $targetCountCache)) {
                $targetCountCache[$targetKey] = $this
                    ->targetStudentQuery
                    ->count($course);
            }

            $expectedRecordCount += $targetCountCache[$targetKey];
            $records = $records->merge(
                $lessonSession->attendanceRecords,
            );
        }

        $recordedCount = $records->count();
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
        $missingCount = max(
            $expectedRecordCount - $recordedCount,
            0,
        );

        $attendanceRate = null;
        $confirmedDenominator = $presentCount + $absentCount;

        if (
            $missingCount === 0
            && $lateCount === 0
            && $earlyLeaveCount === 0
            && $confirmedDenominator > 0
        ) {
            $attendanceRate = $presentCount
                / $confirmedDenominator
                * 100;
        }

        return new AttendanceStatistics(
            lessonCount: $lessonSessions->count(),
            expectedRecordCount: $expectedRecordCount,
            recordedCount: $recordedCount,
            missingCount: $missingCount,
            presentCount: $presentCount,
            absentCount: $absentCount,
            lateCount: $lateCount,
            earlyLeaveCount: $earlyLeaveCount,
            attendanceRate: $attendanceRate,
        );
    }

    /**
     * 指定された出欠区分の件数を返す。
     *
     * @param  Collection<int, AttendanceRecord>  $records
     *
     * @param AttendanceStatus $status 設定する状態
     * @return int 取得した整数
     */
    private function countStatus(
        Collection $records,
        AttendanceStatus $status,
    ): int {
        return $records
            ->filter(
                static fn (AttendanceRecord $record): bool => $record->attendance_status === $status,
            )
            ->count();
    }
}
