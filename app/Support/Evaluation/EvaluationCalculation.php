<?php

namespace App\Support\Evaluation;

final readonly class EvaluationCalculation
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param  list<string>  $warnings
     * @param  AttendanceScoreResult  $attendance  出欠評価結果
     * @param  ?float  $totalScore  合計点
     * @param  ?int  $gradeLevel  評定段階
     * @param  bool  $gradingConfigured  評定基準設定済みフラグ
     */
    public function __construct(
        public AttendanceScoreResult $attendance,
        public ?float $totalScore,
        public ?int $gradeLevel,
        public bool $gradingConfigured,
        public array $warnings,
    ) {}

    /**
     * 評価を確定できる状態か判定する。
     *
     * @return bool 判定結果
     */
    public function canConfirm(): bool
    {
        return $this->attendance->isCalculable()
            && $this->totalScore !== null
            && $this->gradeLevel !== null
            && $this->gradingConfigured;
    }

    /**
     * 総合点を画面表示用の文字列で返す。
     *
     * @return string 取得した文字列
     */
    public function totalScoreLabel(): string
    {
        if ($this->totalScore === null) {
            return '-';
        }

        return number_format($this->totalScore, 2);
    }

    /**
     * 5段階評価を画面表示用の文字列で返す。
     *
     * @return string 取得した文字列
     */
    public function gradeLevelLabel(): string
    {
        if ($this->gradeLevel === null) {
            return '-';
        }

        return (string) $this->gradeLevel;
    }
}
