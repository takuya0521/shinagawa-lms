<?php

namespace App\Data;

/**
 * Google Calendar予定の参加者情報を画面へ渡す不変データ。
 */
final readonly class GoogleCalendarAttendee
{
    /**
     * @param  string  $email  メールアドレス
     * @param  ?string  $displayName  表示名
     * @param  string  $responseStatus  出欠回答状態
     * @param  bool  $organizer  主催者かどうか
     * @param  bool  $self  ログインユーザー本人かどうか
     */
    public function __construct(
        public string $email,
        public ?string $displayName,
        public string $responseStatus,
        public bool $organizer,
        public bool $self,
    ) {}

    /**
     * Googleの回答状態を日本語へ変換する。
     *
     * @return string 画面表示用回答状態
     */
    public function responseLabel(): string
    {
        return match ($this->responseStatus) {
            'accepted' => '参加',
            'declined' => '不参加',
            'tentative' => '未定',
            'needsAction' => '未回答',
            default => $this->responseStatus,
        };
    }
}
