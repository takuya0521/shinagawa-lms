<?php

namespace App\Data;

/**
 * Google Chatスペースのメンバー情報を画面へ渡す不変データ。
 */
final readonly class GoogleChatMembership
{
    /**
     * @param  string  $id  メンバーシップID
     * @param  string  $resourceName  メンバーシップのリソース名
     * @param  string  $memberName  Googleユーザーのリソース名
     * @param  string  $displayName  表示名
     * @param  ?string  $email  メールアドレス
     * @param  string  $state  参加状態
     * @param  string  $role  スペース内ロール
     * @param  string  $type  メンバー種別
     */
    public function __construct(
        public string $id,
        public string $resourceName,
        public string $memberName,
        public string $displayName,
        public ?string $email,
        public string $state,
        public string $role,
        public string $type,
    ) {}

    /**
     * Googleのメンバーロールを日本語へ変換する。
     *
     * @return string 画面表示用ロール
     */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'ROLE_MANAGER' => 'スペース管理者',
            'ROLE_MEMBER' => 'メンバー',
            default => $this->role,
        };
    }
}
