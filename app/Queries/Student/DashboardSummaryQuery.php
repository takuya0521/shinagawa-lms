<?php

namespace App\Queries\Student;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Models\AttendanceRecord;
use App\Models\FinalEvaluation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

final class DashboardSummaryQuery
{
    /**
     * 生徒トップに表示する評価・出欠サマリーを取得する。
     *
     * @param  Student  $student  対象生徒
     * @param  int  $academicYear  対象年度
     * @return array{
     *     evaluation: array{
     *         count: int,
     *         grade_level: float|null,
     *         total_score: float|null,
     *         submission_score: float|null,
     *         attendance_score: float|null,
     *         attitude_score: float|null
     *     },
     *     attendance: array{
     *         total: int,
     *         attended: int,
     *         present: int,
     *         absent: int,
     *         late: int,
     *         early_leave: int,
     *         rate: int|null
     *     }
     * }
     */
    public function execute(
        Student $student,
        int $academicYear,
    ): array {
        $evaluations = FinalEvaluation::query()
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('term_name', EvaluationTerm::Annual->value)
            ->where('status', EvaluationStatus::Confirmed->value)
            ->get([
                'submission_score',
                'attendance_score',
                'attitude_score',
                'total_score',
                'grade_level',
            ]);

        $attendanceCounts = AttendanceRecord::query()
            ->selectRaw('attendance_status, COUNT(*) AS record_count')
            ->where('student_id', $student->id)
            ->whereHas(
                'lessonSession.timetableSlot.course',
                static function (Builder $query) use ($academicYear): void {
                    $query->where('academic_year', $academicYear);
                },
            )
            ->groupBy('attendance_status')
            ->pluck('record_count', 'attendance_status')
            ->map(static fn (mixed $count): int => (int) $count);

        $present = $attendanceCounts->get(
            AttendanceStatus::Present->value,
            0,
        );
        $absent = $attendanceCounts->get(
            AttendanceStatus::Absent->value,
            0,
        );
        $late = $attendanceCounts->get(
            AttendanceStatus::Late->value,
            0,
        );
        $earlyLeave = $attendanceCounts->get(
            AttendanceStatus::EarlyLeave->value,
            0,
        );
        $total = $present + $absent + $late + $earlyLeave;
        $attended = $present + $late + $earlyLeave;

        return [
            'evaluation' => [
                'count' => $evaluations->count(),
                'grade_level' => $this->average(
                    $evaluations->avg('grade_level'),
                ),
                'total_score' => $this->average(
                    $evaluations->avg('total_score'),
                ),
                'submission_score' => $this->average(
                    $evaluations->avg('submission_score'),
                ),
                'attendance_score' => $this->average(
                    $evaluations->avg('attendance_score'),
                ),
                'attitude_score' => $this->average(
                    $evaluations->avg('attitude_score'),
                ),
            ],
            'attendance' => [
                'total' => $total,
                'attended' => $attended,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'early_leave' => $earlyLeave,
                'rate' => $total === 0
                    ? null
                    : (int) round(($attended / $total) * 100),
            ],
        ];
    }

    /**
     * 集計値を画面表示用の小数1桁へ丸める。
     *
     * @param  mixed  $value  処理対象値
     * @return ?float 算出した数値。未算出時はnull
     */
    private function average(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round((float) $value, 1);
    }
}
