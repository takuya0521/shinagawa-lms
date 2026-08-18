<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Googleフォームの回答概要を画面へ渡す不変データ。
 */
final readonly class GoogleFormResponse
{
    /**
     * @param  string  $id  回答ID
     * @param  ?string  $respondentEmail  回答者メールアドレス
     * @param  CarbonImmutable  $createdAt  回答開始日時
     * @param  CarbonImmutable  $submittedAt  最終送信日時
     * @param  int  $answerCount  回答済み設問数
     * @param  ?float  $totalScore  テスト形式の合計点
     */
    public function __construct(
        public string $id,
        public ?string $respondentEmail,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $submittedAt,
        public int $answerCount,
        public ?float $totalScore,
    ) {}
}
