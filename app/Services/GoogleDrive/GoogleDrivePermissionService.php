<?php

namespace App\Services\GoogleDrive;

use App\Data\GoogleDrivePermission;
use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use App\Models\User;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleDrive\Support\GoogleDriveMapper;
use App\Services\GoogleDrive\Support\GoogleDriveResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Support\Collection;

/**
 * Google Driveファイルの共有権限取得・追加・削除を担当する。
 */
final class GoogleDrivePermissionService
{
    /**
     * 共有権限操作で認証・リソース検証・応答変換の条件を統一するため、共通依存を注入する。
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
     * 指定ファイルの共有権限一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @return Collection<int, GoogleDrivePermission> 指定ファイルの共有権限一覧
     *
     * @throws GoogleDriveResponseException API応答またはファイルIDが不正な場合
     */
    public function permissions(User $user, string $fileId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->resource->fileUrl($fileId).'/permissions',
            ['query' => [
                'fields' => 'permissions(id,type,role,displayName,emailAddress,pendingOwner)',
                'supportsAllDrives' => true,
            ]],
        );
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
     * 指定ファイルへ共有権限を追加する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  string  $email  共有・招待するGoogleアカウントのメールアドレス
     * @param  string  $role  Google側へ設定する権限種別
     * @param  bool  $sendNotification  Googleから通知メールを送信する場合はtrue
     */
    public function createPermission(
        User $user,
        string $fileId,
        string $email,
        string $role,
        bool $sendNotification,
    ): void {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->resource->fileUrl($fileId).'/permissions',
            [
                'query' => [
                    'sendNotificationEmail' => $sendNotification,
                    'supportsAllDrives' => true,
                ],
                'json' => [
                    'type' => 'user',
                    'role' => $role,
                    'emailAddress' => trim($email),
                ],
            ],
        );
    }

    /**
     * 指定ファイルの共有権限を削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  string  $permissionId  共有権限ID
     */
    public function deletePermission(User $user, string $fileId, string $permissionId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->resource->fileUrl($fileId).'/permissions/'.$this->resource->childResourceId($permissionId),
            ['query' => ['supportsAllDrives' => true]],
        );
    }
}
