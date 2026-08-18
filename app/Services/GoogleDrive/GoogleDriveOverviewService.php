<?php

namespace App\Services\GoogleDrive;

use App\Data\GoogleDriveFile;
use App\Data\GoogleDriveOverview;
use App\Data\GoogleDrivePage;
use App\Data\GoogleSharedDrive;
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
 * Google Drive一覧画面に必要なファイル一覧と共有ドライブを取得する。
 */
final class GoogleDriveOverviewService
{
    private const FILE_CACHE_SECONDS = 180;

    private const SHARED_DRIVE_CACHE_SECONDS = 300;

    /**
     * 一覧APIの選択実行とキャッシュ分離に必要な依存を受け取る。
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
     * 表示条件に一致するファイル一覧と共有ドライブを取得する。
     * 共有ドライブは表示場所を変えても同じため長めに再利用し、ナビゲーションごとの
     * 重複API呼び出しを避ける。両方が未取得の場合だけ同時送信する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string|null  $parentId  表示対象フォルダID
     * @param  string|null  $keyword  ファイル名検索語
     * @param  string|null  $pageToken  次ページ取得トークン
     * @param  string  $viewMode  my-drive、shared、starred、recent、trash、shared-driveのいずれか
     * @param  string|null  $driveId  共有ドライブ表示時のドライブID
     * @return GoogleDriveOverview 一覧画面へ渡すファイルと共有ドライブ
     */
    public function overview(
        User $user,
        ?string $parentId,
        ?string $keyword,
        ?string $pageToken,
        string $viewMode,
        ?string $driveId,
    ): GoogleDriveOverview {
        $fileCacheKey = 'files:'.json_encode([
            'parent_id' => $parentId,
            'keyword' => $keyword,
            'page_token' => $pageToken,
            'view' => $viewMode,
            'drive_id' => $driveId,
        ], JSON_THROW_ON_ERROR);
        $sharedDriveCacheKey = 'shared-drives';
        $page = $this->cache->get($user, 'drive', $fileCacheKey);
        $sharedDrives = $this->cache->get($user, 'drive', $sharedDriveCacheKey);
        $requests = [];

        if (! $page instanceof GoogleDrivePage) {
            $requests['files'] = [
                'method' => 'GET',
                'url' => $this->configuration->filesUri(),
                'options' => ['query' => $this->fileQuery(
                    $parentId,
                    $keyword,
                    $pageToken,
                    $viewMode,
                    $driveId,
                )],
            ];
        }

        if (! $sharedDrives instanceof Collection) {
            $requests['shared_drives'] = [
                'method' => 'GET',
                'url' => $this->configuration->drivesUri(),
                'options' => ['query' => [
                    'fields' => 'drives(id,name)',
                    'pageSize' => 100,
                ]],
            ];
        }

        if ($requests !== []) {
            $responses = $this->client->sendMany(
                $user,
                $this->configuration->requiredScopes(),
                $requests,
            );

            if (isset($responses['files'])) {
                $page = $this->page($responses['files']);
                $this->cache->put(
                    $user,
                    'drive',
                    $fileCacheKey,
                    self::FILE_CACHE_SECONDS,
                    $page,
                );
            }

            if (isset($responses['shared_drives'])) {
                $sharedDrives = $this->sharedDrives($responses['shared_drives']);
                $this->cache->put(
                    $user,
                    'drive',
                    $sharedDriveCacheKey,
                    self::SHARED_DRIVE_CACHE_SECONDS,
                    $sharedDrives,
                );
            }
        }

        return new GoogleDriveOverview(
            page: $page instanceof GoogleDrivePage ? $page : new GoogleDrivePage(collect(), null),
            sharedDrives: $sharedDrives instanceof Collection ? $sharedDrives : collect(),
        );
    }

    /**
     * Drive files.listへ渡す表示条件を組み立てる。
     *
     * @param  string|null  $parentId  表示対象フォルダID
     * @param  string|null  $keyword  ファイル名検索語
     * @param  string|null  $pageToken  次ページ取得トークン
     * @param  string  $viewMode  Drive表示区分
     * @param  string|null  $driveId  共有ドライブID
     * @return array<string, mixed> Google Drive APIのクエリパラメーター
     */
    private function fileQuery(
        ?string $parentId,
        ?string $keyword,
        ?string $pageToken,
        string $viewMode,
        ?string $driveId,
    ): array {
        $queryParts = [];
        $orderBy = 'folder,name_natural';
        $query = [
            'fields' => 'nextPageToken,files('.GoogleDriveApiContract::FILE_LIST_FIELDS.')',
            'includeItemsFromAllDrives' => true,
            'pageSize' => $this->configuration->pageSize(),
            'spaces' => 'drive',
            'supportsAllDrives' => true,
        ];

        if ($viewMode === 'shared-drive') {
            $validatedDriveId = $this->resource->fileId((string) $driveId);
            $folderId = $parentId !== null && $parentId !== ''
                ? $this->resource->fileId($parentId)
                : $validatedDriveId;
            $queryParts[] = sprintf("'%s' in parents", $folderId);
            $queryParts[] = 'trashed = false';
            $query['corpora'] = 'drive';
            $query['driveId'] = $validatedDriveId;
        } elseif ($viewMode === 'shared') {
            $queryParts[] = 'sharedWithMe = true';
            $queryParts[] = 'trashed = false';
            $orderBy = 'sharedWithMeTime desc';
        } elseif ($viewMode === 'starred') {
            $queryParts[] = 'starred = true';
            $queryParts[] = 'trashed = false';
            $orderBy = 'modifiedTime desc';
        } elseif ($viewMode === 'recent') {
            $queryParts[] = 'trashed = false';
            $orderBy = 'modifiedTime desc';
        } elseif ($viewMode === 'trash') {
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

        return $query;
    }

    /**
     * files.list応答を画面表示用ページへ変換する。
     *
     * @param  Response  $response  Google Drive files.list応答
     * @return GoogleDrivePage ファイル一覧と次ページトークン
     */
    private function page(Response $response): GoogleDrivePage
    {
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
     * drives.list応答を共有ドライブ一覧へ変換する。
     *
     * @param  Response  $response  Google Drive drives.list応答
     * @return Collection<int, GoogleSharedDrive> 利用者が参照できる共有ドライブ一覧
     */
    private function sharedDrives(Response $response): Collection
    {
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
}
