<?php

namespace App\Data;

/**
 * Google Driveの共有権限を画面へ渡す不変データ。
 */
final readonly class GoogleDrivePermission
{
    /**
     * @param  string  $id  権限ID
     * @param  string  $type  権限対象種別
     * @param  string  $role  権限ロール
     * @param  ?string  $displayName  表示名
     * @param  ?string  $emailAddress  メールアドレス
     * @param  bool  $pendingOwner  所有権移譲待ちかどうか
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $role,
        public ?string $displayName,
        public ?string $emailAddress,
        public bool $pendingOwner,
    ) {}

    /**
     * Googleの権限コードを日本語へ変換する。
     *
     * @return string 画面表示用権限名
     */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'owner' => '所有者',
            'organizer' => '管理者',
            'fileOrganizer' => 'コンテンツ管理者',
            'writer' => '編集者',
            'commenter' => 'コメント可',
            'reader' => '閲覧者',
            default => $this->role,
        };
    }
}
