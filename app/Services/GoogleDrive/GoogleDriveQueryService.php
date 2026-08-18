<?php

namespace App\Services\GoogleDrive;

use App\Data\GoogleDriveFile;
use App\Data\GoogleDrivePage;
use App\Data\GoogleSharedDrive;
use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleDrive\Support\GoogleDriveApiContract;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleDrive\Support\GoogleDriveMapper;
use App\Services\GoogleDrive\Support\GoogleDriveResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/**
 * Google Driveの一覧検索、共有ドライブ、ファイル詳細取得を担当する。
 */
final class GoogleDriveQueryService
{
    /**
     * 一覧検索・共有ドライブ・詳細取得で認証と応答変換の条件を統一するため、共通依存を注入する。
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
     * Google API連携に必要な設定が揃っているか判定する。
     *
     * @return bool Google API利用に必要な設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->configuration->isConfigured();
    }

    /**
     * Drive APIへ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Drive APIへ要求するOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return $this->configuration->requiredScopes();
    }

    /**
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  ?string  $parentId  保存先フォルダID
     * @param  ?string  $keyword  検索キーワード
     * @param  ?string  $pageToken  次ページ取得トークン
     * @param  bool  $includeTrashed  ゴミ箱内を含めるかどうか
     * @param  string  $viewMode  一覧の表示区分
     * @param  ?string  $driveId  共有ドライブID
     * @return GoogleDrivePage 取得したファイル一覧と次ページトークン
     *
     * @throws GoogleDriveResponseException Googleの一覧応答を変換できない場合
     */
    public function files(
        User $user,
        ?string $parentId = null,
        ?string $keyword = null,
        ?string $pageToken = null,
        bool $includeTrashed = false,
        string $viewMode = 'my-drive',
        ?string $driveId = null,
    ): GoogleDrivePage {
        $mode = $includeTrashed ? 'trash' : $viewMode;
        $queryParts = [];
        $orderBy = 'folder,name_natural';
        $query = [
            'fields' => 'nextPageToken,files('.GoogleDriveApiContract::FILE_FIELDS.')',
            'includeItemsFromAllDrives' => true,
            'pageSize' => $this->configuration->pageSize(),
            'spaces' => 'drive',
            'supportsAllDrives' => true,
        ];

        if ($mode === 'shared-drive') {
            $validatedDriveId = $this->resource->fileId((string) $driveId);
            $folderId = $parentId !== null && $parentId !== ''
                ? $this->resource->fileId($parentId)
                : $validatedDriveId;
            $queryParts[] = sprintf("'%s' in parents", $folderId);
            $queryParts[] = 'trashed = false';
            $query['corpora'] = 'drive';
            $query['driveId'] = $validatedDriveId;
        } elseif ($mode === 'shared') {
            $queryParts[] = 'sharedWithMe = true';
            $queryParts[] = 'trashed = false';
            $orderBy = 'sharedWithMeTime desc';
        } elseif ($mode === 'starred') {
            $queryParts[] = 'starred = true';
            $queryParts[] = 'trashed = false';
            $orderBy = 'modifiedTime desc';
        } elseif ($mode === 'recent') {
            $queryParts[] = 'trashed = false';
            $orderBy = 'modifiedTime desc';
        } elseif ($mode === 'trash') {
            $queryParts[] = 'trashed = true';
            $orderBy = 'modifiedTime desc';
        } else {
            $folderId = $parentId !== null && $parentId !== ''
                ? $this->resource->fileId($parentId)
                : 'root';
            $queryParts[] = sprintf("'%s' in parents", $folderId);
            $queryParts[] = 'trashed = false';
        }

        if ($keyword !== null && trim($keyword) !== '') {
            $escapedKeyword = str_replace(['\\', "'"], ['\\\\', "\\'"], trim($keyword));
            $queryParts[] = "name contains '{$escapedKeyword}'";
        }

        $query['orderBy'] = $orderBy;
        $query['q'] = implode(' and ', $queryParts);

        if ($pageToken !== null && $pageToken !== '') {
            $query['pageToken'] = mb_substr($pageToken, 0, 2048);
        }

        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->filesUri(),
            ['query' => $query],
        );
        $files = $response->json('files');

        if ($files === null) {
            $files = [];
        }

        if (! is_array($files)) {
            throw new GoogleDriveResponseException('Google Drive API応答にファイル一覧がありません。');
        }

        $nextPageToken = $response->json('nextPageToken');

        return new GoogleDrivePage(
            files: collect($files)
                ->filter(static fn (mixed $file): bool => is_array($file))
                ->map(fn (array $file): GoogleDriveFile => $this->mapper->file($file))
                ->values(),
            nextPageToken: is_string($nextPageToken) && $nextPageToken !== '' ? $nextPageToken : null,
        );
    }

    /**
     * 利用者が参照できる共有ドライブ一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @return Collection<int, GoogleSharedDrive> 利用者が参照できる共有ドライブ一覧
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Drive APIがエラーを返した場合
     * @throws GoogleDriveResponseException API応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function sharedDrives(User $user): Collection
    {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->drivesUri(),
            ['query' => ['fields' => 'drives(id,name)', 'pageSize' => 100]],
        );
        $drives = $response->json('drives');

        if ($drives === null) {
            return collect();
        }

        if (! is_array($drives)) {
            throw new GoogleDriveResponseException('Google Drive API応答に共有ドライブ一覧がありません。');
        }

        return collect($drives)
            ->filter(static fn (mixed $drive): bool => is_array($drive))
            ->map(fn (array $drive): GoogleSharedDrive => $this->mapper->sharedDrive($drive))
            ->values();
    }

    /**
     * 指定IDのGoogle Driveファイル情報を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @return GoogleDriveFile 指定IDに対応するファイル情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Drive APIがエラーを返した場合
     * @throws GoogleDriveResponseException API応答またはファイルIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function file(User $user, string $fileId): GoogleDriveFile
    {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->resource->fileUrl($fileId),
            ['query' => ['fields' => GoogleDriveApiContract::FILE_FIELDS, 'supportsAllDrives' => true]],
        );
        $file = $response->json();

        if (! is_array($file)) {
            throw new GoogleDriveResponseException('Google Drive API応答にファイル情報がありません。');
        }

        return $this->mapper->file($file);
    }
}
