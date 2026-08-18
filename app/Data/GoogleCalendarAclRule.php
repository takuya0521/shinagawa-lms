<?php

namespace App\Data;

/**
 * Google Calendarの共有ルールを画面へ渡す不変データ。
 */
final readonly class GoogleCalendarAclRule
{
    /**
     * @param  string  $id  ACLルールID
     * @param  string  $scopeType  共有対象種別
     * @param  ?string  $scopeValue  メールアドレスまたはドメイン
     * @param  string  $role  共有権限
     */
    public function __construct(
        public string $id,
        public string $scopeType,
        public ?string $scopeValue,
        public string $role,
    ) {}

    /**
     * GoogleのACLロールを日本語へ変換する。
     *
     * @return string 画面表示用権限名
     */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'none' => 'アクセスなし',
            'freeBusyReader' => '予定あり・なしのみ',
            'reader' => '閲覧者',
            'writer' => '編集者',
            'owner' => '管理者',
            default => $this->role,
        };
    }
}
