<?php

namespace App\Support\Evaluation;

final readonly class AttendanceScoreResult
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param  ?float  $score  得点
     * @param  int  $lessonCount  授業実施件数
     * @param  int  $recordedCount  登録済み件数
     * @param  int  $presentCount  出席件数
     * @param  int  $absentCount  欠席件数
     * @param  int  $lateCount  遅刻件数
     * @param  int  $earlyLeaveCount  早退件数
     * @param  int  $missingCount  未登録件数
     */
    public function __construct(
        public ?float $score,
        public int $lessonCount,
        public int $recordedCount,
        public int $presentCount,
        public int $absentCount,
        public int $lateCount,
        public int $earlyLeaveCount,
        public int $missingCount,
    ) {}

    /**
     * 現時点の確定仕様だけで出欠点を算出できるか判定する。
     *
     * @return bool 判定結果
     */
    public function isCalculable(): bool
    {
        return $this->score !== null;
    }

    /**
     * 出欠点を画面表示用の文字列で返す。
     *
     * @return string 取得した文字列
     */
    public function scoreLabel(): string
    {
        if ($this->score === null) {
            return '-';
        }

        return number_format($this->score, 2);
    }

    /**
     * 算出できない理由を返す。
     *
     * @return ?string 取得した文字列。未指定時はnull
     */
    public function unavailableReason(): ?string
    {
        if ($this->lessonCount === 0) {
            return '実施済み授業がないため、出欠点を算出できません。';
        }

        if ($this->missingCount > 0) {
            return '未登録の出欠が含まれるため、出欠点を算出できません。';
        }

        if ($this->lateCount > 0 || $this->earlyLeaveCount > 0) {
            return '遅刻・早退の換算規則が未決のため、出欠点を算出できません。';
        }

        return null;
    }
}
