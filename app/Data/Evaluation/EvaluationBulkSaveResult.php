<?php

namespace App\Data\Evaluation;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Models\Course;

/**
 * 最終評価一括保存の結果を保持する。
 */
final readonly class EvaluationBulkSaveResult
{
    /**
     * 最終評価一括保存結果を生成する。
     *
     * @param  Course  $course  保存対象の授業
     * @param  int  $academicYear  評価年度
     * @param  EvaluationTerm  $term  評価学期
     * @param  EvaluationStatus  $status  保存した評価状態
     * @param  int  $savedCount  保存した評価数
     */
    public function __construct(
        public Course $course,
        public int $academicYear,
        public EvaluationTerm $term,
        public EvaluationStatus $status,
        public int $savedCount,
    ) {}
}
