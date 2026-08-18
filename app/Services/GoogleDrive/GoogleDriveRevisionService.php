<?php

namespace App\Services\GoogleDrive;

use App\Data\GoogleDriveRevision;
use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use App\Models\User;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleDrive\Support\GoogleDriveMapper;
use App\Services\GoogleDrive\Support\GoogleDriveResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Support\Collection;

/**
 * Google Driveファイルの版履歴取得を担当する。
 */
final class GoogleDriveRevisionService
{
    /**
     * 版履歴取得でも他のDrive操作と同じ認証・ID検証条件を使うため、共通依存を注入する。
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
     * 指定ファイルの版履歴一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @return Collection<int, GoogleDriveRevision> 指定ファイルの版履歴一覧
     *
     * @throws GoogleDriveResponseException API応答またはファイルIDが不正な場合
     */
    public function revisions(User $user, string $fileId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->resource->fileUrl($fileId).'/revisions',
            ['query' => [
                'fields' => 'revisions(id,modifiedTime,lastModifyingUser(displayName),size,keepForever)',
                'pageSize' => 100,
            ]],
        );
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
