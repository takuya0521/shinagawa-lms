<?php

namespace App\Queries\Dashboard;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Queries\Attendance\TargetStudentQuery;
use Illuminate\Database\Eloquent\Collection;

/**
 * 授業ごとの未確定評価数を集計する。
 */
final readonly class PendingEvaluationCountQuery
{
    /**
     * 必要な検索処理を受け取る。
     *
     * @param  TargetStudentQuery  $targetStudentQuery  授業対象の在籍生徒数を取得する検索処理
     */
    public function __construct(
        private TargetStudentQuery $targetStudentQuery,
    ) {}

    /**
     * 対象授業における年間評価の未確定件数を返す。
     *
     * @param  Collection<int, Course>  $courses  集計対象の授業一覧
     * @param  int  $academicYear  集計対象年度
     * @return int 対象生徒数から確定済み評価数を差し引いた合計
     */
    public function execute(
        Collection $courses,
        int $academicYear,
    ): int {
        $pendingCount = 0;

        foreach ($courses as $course) {
            $targetCount = $this->targetStudentQuery->count($course);
            $confirmedCount = FinalEvaluation::query()
                ->where('course_id', $course->id)
                ->where('academic_year', $academicYear)
                ->where('term_name', EvaluationTerm::Annual->value)
                ->where('status', EvaluationStatus::Confirmed->value)
                ->count();

            $pendingCount += max($targetCount - $confirmedCount, 0);
        }

        return $pendingCount;
    }
}
