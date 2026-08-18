<?php

namespace App\Data;

/**
 * Google Chat APIのスペース情報を画面表示へ渡す不変データ。
 */
final readonly class GoogleChatSpace
{
    /**
     * @param  string  $id  URLへ安全に渡すスペースID
     * @param  string  $resourceName  Google Chat上のリソース名
     * @param  string  $displayName  画面表示名
     * @param  string  $type  Google Chat上のスペース種別
     * @param  ?string  $spaceUri  Google Chatで開くURL
     * @param  ?string  $description  スペース説明
     * @param  string  $historyState  履歴設定
     * @param  ?string  $membershipState  ログインユーザーの参加状態
     * @param  bool  $canManageMembers  メンバー管理が可能かどうか
     */
    public function __construct(
        public string $id,
        public string $resourceName,
        public string $displayName,
        public string $type,
        public ?string $spaceUri,
        public ?string $description,
        public string $historyState,
        public ?string $membershipState,
        public bool $canManageMembers,
    ) {}

    /**
     * Google Chatの種別コードを日本語へ変換する。
     *
     * @return string 画面表示用のスペース種別
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'SPACE' => 'スペース',
            'GROUP_CHAT' => 'グループチャット',
            'DIRECT_MESSAGE' => 'ダイレクトメッセージ',
            default => 'チャット',
        };
    }

    /**
     * 名前付きスペースかどうかを返す。
     *
     * @return bool 名前付きスペースの場合はtrue
     */
    public function isNamedSpace(): bool
    {
        return $this->type === 'SPACE';
    }
}
