<?php

namespace App\Data;

/**
 * Google Calendarのカレンダー情報を画面へ渡す不変データ。
 */
final readonly class GoogleCalendarCalendar
{
    /**
     * @param  string  $id  カレンダーID
     * @param  string  $summary  カレンダー名
     * @param  ?string  $description  説明
     * @param  ?string  $timeZone  タイムゾーン
     * @param  string  $accessRole  ログインユーザーの権限
     * @param  bool  $primary  メインカレンダーかどうか
     * @param  bool  $selected  Google Calendar上で表示対象かどうか
     * @param  ?string  $backgroundColor  Google側の背景色
     */
    public function __construct(
        public string $id,
        public string $summary,
        public ?string $description,
        public ?string $timeZone,
        public string $accessRole,
        public bool $primary,
        public bool $selected,
        public ?string $backgroundColor,
    ) {}

    /**
     * 予定を作成・更新できる権限か判定する。
     *
     * @return bool 書き込み可能な場合はtrue
     */
    public function canWrite(): bool
    {
        return in_array($this->accessRole, ['writer', 'owner'], true);
    }

    /**
     * カレンダー自体を管理できる権限か判定する。
     *
     * @return bool 所有者の場合はtrue
     */
    public function canManage(): bool
    {
        return $this->accessRole === 'owner';
    }
}
