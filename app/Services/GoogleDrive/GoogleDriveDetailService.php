<?php

namespace App\Services\GoogleDrive;

use App\Data\GoogleDriveComment;
use App\Data\GoogleDriveDetail;
use App\Data\GoogleDrivePermission;
use App\Data\GoogleDriveRevision;
use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use App\Models\User;
use App\Services\GoogleDrive\Support\GoogleDriveApiContract;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleDrive\Support\GoogleDriveMapper;
use App\Services\GoogleDrive\Support\GoogleDriveResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * Google Drive詳細画面の4種類のAPIを並列取得する。
 */
final class GoogleDriveDetailService
{
    private const CACHE_SECONDS = 120;

    /**
     * ファイル詳細・共有・コメント・版履歴を同時取得するための依存を受け取る。
     *
     * @param  GoogleApiClient  $client  認証・再試行・並列通信を共通化したGoogle APIクライアント
     * @param  GoogleDriveConfiguration  $configuration  Drive API設定
     * @param  GoogleDriveResource  $resource  DriveリソースID検証
     * @param  GoogleDriveMapper  $mapper  Drive API応答のDTO変換
     * @param  GoogleWorkspaceCache  $cache  利用者単位のSWRキャッシュ
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleDriveConfiguration $configuration,
        private readonly GoogleDriveResource $resource,
        private readonly GoogleDriveMapper $mapper,
        private readonly GoogleWorkspaceCache $cache,
    ) {}

    /**
     * 指定ファイルの詳細情報を同時取得して返す。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveファイルID
     * @return GoogleDriveDetail ファイル詳細画面で使用する情報一式
     */
    public function detail(User $user, string $fileId): GoogleDriveDetail
    {
        $validatedFileId = $this->resource->fileId($fileId);

        return $this->cache->remember(
            $user,
            'drive',
            'detail:'.$validatedFileId,
            self::CACHE_SECONDS,
            function () use ($user, $validatedFileId): GoogleDriveDetail {
                $fileUrl = $this->resource->fileUrl($validatedFileId);
                $responses = $this->client->sendMany(
                    $user,
                    $this->configuration->requiredScopes(),
                    [
                        'file' => [
                            'method' => 'GET',
                            'url' => $fileUrl,
                            'options' => ['query' => [
                                'fields' => GoogleDriveApiContract::FILE_FIELDS,
                                'supportsAllDrives' => true,
                            ]],
                        ],
                        'permissions' => [
                            'method' => 'GET',
                            'url' => $fileUrl.'/permissions',
                            'options' => ['query' => [
                                'fields' => 'permissions(id,type,role,displayName,emailAddress,pendingOwner)',
                                'supportsAllDrives' => true,
                            ]],
                        ],
                        'comments' => [
                            'method' => 'GET',
                            'url' => $fileUrl.'/comments',
                            'options' => ['query' => [
                                'fields' => 'comments('
                                    .'id,content,createdTime,resolved,author(displayName),'
                                    .'replies(id,content,createdTime,author(displayName))'
                                    .')',
                                'includeDeleted' => false,
                                'pageSize' => 100,
                            ]],
                        ],
                        'revisions' => [
                            'method' => 'GET',
                            'url' => $fileUrl.'/revisions',
                            'options' => ['query' => [
                                'fields' => 'revisions('
                                    .'id,modifiedTime,lastModifyingUser(displayName),size,keepForever'
                                    .')',
                                'pageSize' => 100,
                            ]],
                        ],
                    ],
                );

                $file = $responses['file']->json();

                if (! is_array($file)) {
                    throw new GoogleDriveResponseException('Google Drive API応答にファイル情報がありません。');
                }

                return new GoogleDriveDetail(
                    file: $this->mapper->file($file),
                    permissions: $this->permissions($responses['permissions']),
                    comments: $this->comments($responses['comments']),
                    revisions: $this->revisions($responses['revisions']),
                );
            },
        );
    }

    /**
     * permissions.list応答を共有権限一覧へ変換する。
     *
     * @param  Response  $response  Google Drive permissions.list応答
     * @return Collection<int, GoogleDrivePermission> 共有権限一覧
     */
    private function permissions(Response $response): Collection
    {
        $permissions = $response->json('permissions');

        if ($permissions === null) {
            return collect();
        }

        if (! is_array($permissions)) {
            throw new GoogleDriveResponseException('Google Drive API応答に共有権限一覧がありません。');
        }

        return collect($permissions)
            ->filter(static fn (mixed $permission): bool => is_array($permission))
            ->map(fn (array $permission): GoogleDrivePermission => $this->mapper->permission($permission))
            ->values();
    }

    /**
     * comments.list応答をコメント一覧へ変換する。
     *
     * @param  Response  $response  Google Drive comments.list応答
     * @return Collection<int, GoogleDriveComment> コメント一覧
     */
    private function comments(Response $response): Collection
    {
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
     * revisions.list応答を版履歴一覧へ変換する。
     *
     * @param  Response  $response  Google Drive revisions.list応答
     * @return Collection<int, GoogleDriveRevision> 版履歴一覧
     */
    private function revisions(Response $response): Collection
    {
        $revisions = $response->json('revisions');

        if ($revisions === null) {
            return collect();
        }

        if (! is_array($revisions)) {
            throw new GoogleDriveResponseException('Google Drive API応答に版一覧がありません。');
        }

        return collect($revisions)
            ->filter(static fn (mixed $revision): bool => is_array($revision))
            ->map(fn (array $revision): GoogleDriveRevision => $this->mapper->revision($revision))
            ->values();
    }
}
