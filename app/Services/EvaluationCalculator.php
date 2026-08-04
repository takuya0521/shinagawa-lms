<?php

namespace App\Services;

use App\Support\Evaluation\AttendanceScoreResult;
use App\Support\Evaluation\EvaluationCalculation;

final class EvaluationCalculator
{
    /**
     * 教員入力値と自動算出した出欠点から評価を計算する。
     *
     * @param  float  $submissionScore  提出物点
     * @param  AttendanceScoreResult  $attendance  出欠評価結果
     * @param  float  $attitudeScore  授業態度点
     * @return EvaluationCalculation 処理結果
     */
    public function calculate(
        float $submissionScore,
        AttendanceScoreResult $attendance,
        float $attitudeScore,
    ): EvaluationCalculation {
        $warnings = [];
        $totalScore = null;
        $gradeLevel = null;
        $gradingConfigured = $this->isGradingConfigured();

        if (! $attendance->isCalculable()) {
            $reason = $attendance->unavailableReason();

            if ($reason !== null) {
                $warnings[] = $reason;
            }
        } else {
            $totalScore = $this->applyRounding(
                ($submissionScore + $attendance->score + $attitudeScore) / 3,
            );

            if ($gradingConfigured) {
                $gradeLevel = $this->resolveGradeLevel(
                    $totalScore,
                );
            } else {
                $warnings[] = '評価閾値または端数処理が未確定です。確定設定後に評価を確定してください。';
            }
        }

        return new EvaluationCalculation(
            attendance: $attendance,
            totalScore: $totalScore,
            gradeLevel: $gradeLevel,
            gradingConfigured: $gradingConfigured,
            warnings: $warnings,
        );
    }

    /**
     * 管理者が指定した3項目点から評価を再計算する。
     *
     * @param  float  $submissionScore  提出物点
     * @param  float  $attendanceScore  出欠点
     * @param  float  $attitudeScore  授業態度点
     * @return EvaluationCalculation 処理結果
     */
    public function calculateFromScores(
        float $submissionScore,
        float $attendanceScore,
        float $attitudeScore,
    ): EvaluationCalculation {
        $attendance = new AttendanceScoreResult(
            score: $attendanceScore,
            lessonCount: 0,
            recordedCount: 0,
            presentCount: 0,
            absentCount: 0,
            lateCount: 0,
            earlyLeaveCount: 0,
            missingCount: 0,
        );

        return $this->calculate(
            $submissionScore,
            $attendance,
            $attitudeScore,
        );
    }

    /**
     * 画面プレビューで使用する評価設定を返す。
     *
     * @return array{roundingMode: string|null, gradeThresholds: array<int, float>}
     */
    public function previewConfiguration(): array
    {
        if (! $this->isGradingConfigured()) {
            return [
                'roundingMode' => null,
                'gradeThresholds' => [],
            ];
        }

        /** @var string $roundingMode */
        $roundingMode = config('lms.evaluation.rounding_mode');
        /** @var array<int, int|float|string> $configuredThresholds */
        $configuredThresholds = config('lms.evaluation.grade_thresholds');
        $gradeThresholds = [];

        foreach ([5, 4, 3, 2, 1] as $level) {
            $gradeThresholds[$level] = (float) $configuredThresholds[$level];
        }

        return [
            'roundingMode' => $roundingMode,
            'gradeThresholds' => $gradeThresholds,
        ];
    }

    /**
     * 評価閾値と端数処理がすべて設定済みか判定する。
     *
     * @return bool 判定結果
     */
    public function isGradingConfigured(): bool
    {
        $roundingMode = config('lms.evaluation.rounding_mode');
        $thresholds = config('lms.evaluation.grade_thresholds');

        if (! in_array($roundingMode, ['round', 'floor', 'ceil'], true)) {
            return false;
        }

        if (! is_array($thresholds)) {
            return false;
        }

        foreach ([5, 4, 3, 2, 1] as $level) {
            if (
                ! array_key_exists($level, $thresholds)
                || ! is_numeric($thresholds[$level])
            ) {
                return false;
            }
        }

        return (float) $thresholds[5] > (float) $thresholds[4]
            && (float) $thresholds[4] > (float) $thresholds[3]
            && (float) $thresholds[3] > (float) $thresholds[2]
            && (float) $thresholds[2] >= (float) $thresholds[1]
            && (float) $thresholds[1] >= 0;
    }

    /**
     * 設定された方式で小数第2位へ端数処理する。
     *
     * 未設定時は下書きプレビュー用として四捨五入し、確定は別途禁止する。
     *
     * @param  float  $score  得点
     * @return float 算出した数値
     */
    private function applyRounding(float $score): float
    {
        $roundingMode = config('lms.evaluation.rounding_mode');
        $scaledScore = $score * 100;

        return match ($roundingMode) {
            'floor' => floor($scaledScore) / 100,
            'ceil' => ceil($scaledScore) / 100,
            default => round($score, 2),
        };
    }

    /**
     * 設定された閾値から5段階評価を返す。
     *
     * @param  float  $totalScore  合計点
     * @return int 取得した整数
     */
    private function resolveGradeLevel(float $totalScore): int
    {
        /** @var array<int, int|float|string> $thresholds */
        $thresholds = config('lms.evaluation.grade_thresholds');

        foreach ([5, 4, 3, 2] as $level) {
            if ($totalScore >= (float) $thresholds[$level]) {
                return $level;
            }
        }

        return 1;
    }
}
