<?php

namespace App\Actions\Admin;

use App\Enums\EvaluationStatus;
use App\Models\FinalEvaluation;
use App\Models\User;
use App\Services\EvaluationCalculator;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CorrectFinalEvaluationAction
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param EvaluationCalculator $evaluationCalculator 評価計算サービス
     * @param OperationLogWriter $operationLogWriter 操作ログ記録サービス
     */
    public function __construct(
        private readonly EvaluationCalculator $evaluationCalculator,
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * 管理者による評価修正を保存し、修正前後と理由を操作ログへ残す。
     *
     * @param  array{submission_score: float, attendance_score: float, attitude_score: float, correction_reason: string}  $attributes
     *
     * @param FinalEvaluation $finalEvaluation 対象の最終評価
     * @param User $user 対象ユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return FinalEvaluation 処理結果
     */
    public function execute(
        FinalEvaluation $finalEvaluation,
        array $attributes,
        User $user,
        ?string $ipAddress,
    ): FinalEvaluation {
        return DB::transaction(function () use (
            $finalEvaluation,
            $attributes,
            $user,
            $ipAddress,
        ): FinalEvaluation {
            $lockedEvaluation = FinalEvaluation::query()
                ->lockForUpdate()
                ->findOrFail($finalEvaluation->id);
            $before = $this->snapshot($lockedEvaluation);
            $calculation = $this->evaluationCalculator->calculateFromScores(
                $attributes['submission_score'],
                $attributes['attendance_score'],
                $attributes['attitude_score'],
            );

            if (
                $lockedEvaluation->status === EvaluationStatus::Confirmed
                && ! $calculation->canConfirm()
            ) {
                throw ValidationException::withMessages([
                    'submission_score' => $calculation->warnings[0]
                        ?? '評価設定が未確定のため、確定済み評価を再計算できません。',
                ]);
            }

            $lockedEvaluation->update([
                'submission_score' => $attributes['submission_score'],
                'attendance_score' => $attributes['attendance_score'],
                'attitude_score' => $attributes['attitude_score'],
                'total_score' => $calculation->totalScore ?? 0,
                'grade_level' => $calculation->gradeLevel,
            ]);
            $lockedEvaluation->refresh();

            $this->operationLogWriter->write(
                actor: $user,
                action: 'evaluation_correct',
                target: $lockedEvaluation,
                detail: [
                    'before' => $before,
                    'after' => $this->snapshot($lockedEvaluation),
                    'correction_reason' => $attributes['correction_reason'],
                ],
                ipAddress: $ipAddress,
            );

            return $lockedEvaluation;
        });
    }

    /**
     * 操作ログへ保存する評価スナップショットを返す。
     *
     * @param FinalEvaluation $finalEvaluation 対象の最終評価
     * @return array<string, mixed>
     */
    private function snapshot(
        FinalEvaluation $finalEvaluation,
    ): array {
        return [
            'submission_score' => $finalEvaluation->submission_score,
            'attendance_score' => $finalEvaluation->attendance_score,
            'attitude_score' => $finalEvaluation->attitude_score,
            'total_score' => $finalEvaluation->total_score,
            'grade_level' => $finalEvaluation->grade_level,
            'status' => $finalEvaluation->status->value,
        ];
    }
}
