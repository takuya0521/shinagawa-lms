<?php

namespace App\Support\Evaluation;

use App\Models\FinalEvaluation;
use App\Models\Student;

final readonly class EvaluationEntryRow
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param Student $student 対象生徒
     * @param ?FinalEvaluation $evaluation 最終評価
     * @param float $submissionScore 提出物点
     * @param float $attitudeScore 授業態度点
     * @param EvaluationCalculation $calculation 評価計算結果
     */
    public function __construct(
        public Student $student,
        public ?FinalEvaluation $evaluation,
        public float $submissionScore,
        public float $attitudeScore,
        public EvaluationCalculation $calculation,
    ) {}
}
