<?php

namespace App\Services\GoogleDrive;

use App\Data\GoogleDriveComment;
use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use App\Models\User;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleDrive\Support\GoogleDriveMapper;
use App\Services\GoogleDrive\Support\GoogleDriveResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Support\Collection;

/**
 * Google Driveファイルのコメント・返信操作を担当する。
 */
final class GoogleDriveCommentService
{
    /**
     * コメント操作で認証・リソース検証・応答変換の条件を統一するため、共通依存を注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleDriveConfiguration  $configuration  Drive APIのURI・スコープ・取得上限を提供する設定サービス
     * @param  GoogleDriveResource  $resource  GoogleリソースIDとURLを安全に扱うサービス
     * @param  GoogleDriveMapper  $mapper  Google API応答をDTOへ変換するサービス
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleDriveConfiguration $configuration,
        private readonly GoogleDriveResource $resource,
        private readonly GoogleDriveMapper $mapper,
    ) {}

    /**
     * 指定ファイルのコメント一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @return Collection<int, GoogleDriveComment> 指定ファイルのコメント一覧
     *
     * @throws GoogleDriveResponseException API応答またはファイルIDが不正な場合
     */
    public function comments(User $user, string $fileId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->resource->fileUrl($fileId).'/comments',
            ['query' => [
                'fields' => 'comments(id,content,createdTime,resolved,author(displayName),'
                    .'replies(id,content,createdTime,author(displayName)))',
                'includeDeleted' => false,
                'pageSize' => 100,
            ]],
        );
        $comments = $response->json('comments');

        if ($comments === null) {
            return collect();
        }

        if (! is_array($comments)) {
            throw new GoogleDriveResponseException('Google Drive API応答にコメント一覧がありません。');
        }

        return collect($comments)
            ->filter(static fn (mixed $comment): bool => is_array($comment))
            ->map(fn (array $comment): GoogleDriveComment => $this->mapper->comment($comment))
            ->values();
    }

    /**
     * 指定ファイルへコメントを追加する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  string  $content  投稿する本文
     */
    public function createComment(User $user, string $fileId, string $content): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->resource->fileUrl($fileId).'/comments',
            ['query' => ['fields' => 'id'], 'json' => ['content' => trim($content)]],
        );
    }

    /**
     * 指定コメントへ返信を追加する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  string  $commentId  コメントID
     * @param  string  $content  投稿する本文
     */
    public function createReply(User $user, string $fileId, string $commentId, string $content): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->resource->fileUrl($fileId).'/comments/'.$this->resource->childResourceId($commentId).'/replies',
            ['query' => ['fields' => 'id'], 'json' => ['content' => trim($content)]],
        );
    }

    /**
     * 指定ファイルのコメントを削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  string  $commentId  コメントID
     */
    public function deleteComment(User $user, string $fileId, string $commentId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->resource->fileUrl($fileId).'/comments/'.$this->resource->childResourceId($commentId),
        );
    }
}
