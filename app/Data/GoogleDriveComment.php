<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Google Driveのコメントと返信を画面へ渡す不変データ。
 */
final readonly class GoogleDriveComment
{
    /**
     * @param  string  $id  コメントID
     * @param  string  $content  コメント本文
     * @param  string  $authorName  投稿者名
     * @param  CarbonImmutable  $createdAt  投稿日時
     * @param  bool  $resolved  解決済みかどうか
     * @param  Collection  $replies  返信一覧
     *
     * @phpstan-param Collection<int, array{
     *     id: string,
     *     content: string,
     *     author: string,
     *     created_at: CarbonImmutable
     * }> $replies
     */
    public function __construct(
        public string $id,
        public string $content,
        public string $authorName,
        public CarbonImmutable $createdAt,
        public bool $resolved,
        public Collection $replies,
    ) {}
}
