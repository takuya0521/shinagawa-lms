<?php

namespace App\Data;

/**
 * Google Meetの会議スペース情報を画面へ渡す不変データ。
 */
final readonly class GoogleMeetSpace
{
    /**
     * @param  string  $name  Google Meetスペースのリソース名
     * @param  string  $meetingCode  利用者が入力できる会議コード
     * @param  string  $meetingUri  Google Meet参加URL
     * @param  ?string  $activeConferenceName  開催中会議のリソース名
     */
    public function __construct(
        public string $name,
        public string $meetingCode,
        public string $meetingUri,
        public ?string $activeConferenceName,
    ) {}

    /**
     * 開催中の会議があるか判定する。
     *
     * @return bool 開催中会議がある場合はtrue
     */
    public function isActive(): bool
    {
        return $this->activeConferenceName !== null;
    }
}
