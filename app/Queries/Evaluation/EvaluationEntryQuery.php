<?php

namespace App\Queries\Evaluation;

use App\Enums\EvaluationTerm;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Queries\Attendance\TargetStudentQuery;
use App\Services\EvaluationCalculator;
use App\Support\Evaluation\EvaluationEntryRow;
use Illuminate\Support\Collection;

final class EvaluationEntryQuery
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param TargetStudentQuery $targetStudentQuery データ取得処理
     * @param AttendanceScoreQuery $attendanceScoreQuery データ取得処理
     * @param EvaluationCalculator $evaluationCalculator 評価計算サービス
     */
    public function __construct(
        private readonly TargetStudentQuery $targetStudentQuery,
        private readonly AttendanceScoreQuery $attendanceScoreQuery,
        private readonly EvaluationCalculator $evaluationCalculator,
    ) {}

    /**
     * 対象授業の生徒と既存評価を入力行として返す。
     *
     * @param Course $course 対象授業
     * @param int $academicYear 対象年度
     * @param EvaluationTerm $term 対象学期
     * @return Collection<int, EvaluationEntryRow>
     */
    public function execute(
        Course $course,
        int $academicYear,
        EvaluationTerm $term,
    ): Collection {
        $students = $this->targetStudentQuery->execute($course);
        $evaluations = FinalEvaluation::query()
            ->where('course_id', $course->id)
            ->where('academic_year', $academicYear)
            ->where('term_name', $term->value)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');
        $attendanceScores = $this->attendanceScoreQuery->execute(
            $course,
            $students,
        );

        return $students
            ->map(function ($student) use (
                $evaluations,
                $attendanceScores,
            ): EvaluationEntryRow {
                $evaluation = $evaluations->get($student->id);
                $finalEvaluation = $evaluation instanceof FinalEvaluation
                    ? $evaluation
                    : null;
                $submissionScore = $finalEvaluation === null
                    ? 0.0
                    : (float) $finalEvaluation->submission_score;
                $attitudeScore = $finalEvaluation === null
                    ? 0.0
                    : (float) $finalEvaluation->attitude_score;
                $calculation = $this->evaluationCalculator->calculate(
                    $submissionScore,
                    $attendanceScores[$student->id],
                    $attitudeScore,
                );

                return new EvaluationEntryRow(
                    student: $student,
                    evaluation: $finalEvaluation,
                    submissionScore: $submissionScore,
                    attitudeScore: $attitudeScore,
                    calculation: $calculation,
                );
            })
            ->values();
    }
}
