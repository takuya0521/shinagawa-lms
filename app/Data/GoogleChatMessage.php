<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Google Chat APIのメッセージを画面表示へ渡す不変データ。
 */
final readonly class GoogleChatMessage
{
    /**
     * @param  string  $id  URLへ安全に渡すメッセージID
     * @param  string  $resourceName  Google Chat上のメッセージリソース名
     * @param  string  $senderName  送信者表示名
     * @param  ?string  $senderResourceName  送信者のGoogleユーザーリソース名
     * @param  string  $text  プレーンテキスト本文
     * @param  CarbonImmutable  $createdAt  送信日時
     * @param  ?CarbonImmutable  $updatedAt  更新日時
     * @param  ?string  $threadName  スレッドリソース名
     * @param  Collection<int, GoogleChatReaction>  $reactions  リアクション一覧
     * @param  array  $attachments  添付ファイル一覧
     * @param  bool  $deleted  削除済みかどうか
     *
     * @phpstan-param list<array{
     *     name: string,
     *     content_name: string,
     *     content_type: string,
     *     download_uri: string|null
     * }> $attachments
     */
    public function __construct(
        public string $id,
        public string $resourceName,
        public string $senderName,
        public ?string $senderResourceName,
        public string $text,
        public CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public ?string $threadName,
        public Collection $reactions,
        public array $attachments,
        public bool $deleted,
    ) {}
}
