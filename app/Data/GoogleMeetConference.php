<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Google Meetの会議履歴を画面へ渡す不変データ。
 */
final readonly class GoogleMeetConference
{
    /**
     * @param  string  $name  会議履歴のリソース名
     * @param  string  $spaceName  会議スペースのリソース名
     * @param  CarbonImmutable  $startedAt  開始日時
     * @param  ?CarbonImmutable  $endedAt  終了日時
     * @param  ?CarbonImmutable  $expiresAt  Google側の履歴削除予定日時
     */
    public function __construct(
        public string $name,
        public string $spaceName,
        public CarbonImmutable $startedAt,
        public ?CarbonImmutable $endedAt,
        public ?CarbonImmutable $expiresAt,
    ) {}

    /**
     * 会議が開催中か判定する。
     *
     * @return bool 終了日時がない場合はtrue
     */
    public function isActive(): bool
    {
        return $this->endedAt === null;
    }

    /**
     * 会議時間を利用者向け表示へ変換する。
     *
     * @return string 開催中表示または秒・分単位の経過時間
     */
    public function durationLabel(): string
    {
        if ($this->endedAt === null) {
            return '開催中';
        }

        $totalSeconds = (int) round($this->startedAt->diffInSeconds($this->endedAt));

        if ($totalSeconds < 60) {
            return $totalSeconds.'秒';
        }

        $minutes = intdiv($totalSeconds, 60);
        $seconds = $totalSeconds % 60;

        return $seconds === 0
            ? $minutes.'分'
            : $minutes.'分'.$seconds.'秒';
    }

    /**
     * 会議履歴の短い識別子を返す。
     *
     * @return string 画面表示用識別子
     */
    public function shortId(): string
    {
        return basename($this->name);
    }
}
