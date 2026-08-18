<?php

namespace App\Services\GoogleDrive;

use App\Data\GoogleDriveFile;
use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleDrive\Support\GoogleDriveApiContract;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleDrive\Support\GoogleDriveMapper;
use App\Services\GoogleDrive\Support\GoogleDriveResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;

/**
 * Google Driveの作成、アップロード、更新、複製、削除を担当する。
 */
final class GoogleDriveMutationService
{
    /**
     * 作成・更新・複製・削除で認証・ID検証・応答変換の条件を統一するため、共通依存を注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleDriveConfiguration  $configuration  Drive APIのURI・スコープ・取得上限を提供する設定サービス
     * @param  GoogleDriveResource  $resource  GoogleリソースIDとURLを安全に扱うサービス
     * @param  GoogleDriveMapper  $mapper  Google API応答をDTOへ変換するサービス
     * @param  GoogleDriveQueryService  $queries  Google Driveの参照処理を担当するサービス
     * @param  GoogleWorkspaceCache  $cache  アップロード成功後に一覧キャッシュを無効化するサービス
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleDriveConfiguration $configuration,
        private readonly GoogleDriveResource $resource,
        private readonly GoogleDriveMapper $mapper,
        private readonly GoogleDriveQueryService $queries,
        private readonly GoogleWorkspaceCache $cache,
    ) {}

    /**
     * Google Driveに新しいフォルダを作成する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $name  表示名
     * @param  ?string  $parentId  保存先フォルダID
     * @return GoogleDriveFile Google APIが返した作成後のフォルダ情報
     */
    public function createFolder(User $user, string $name, ?string $parentId): GoogleDriveFile
    {
        return $this->createMetadata($user, [
            'name' => trim($name),
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$this->resource->parentId($parentId)],
        ]);
    }

    /**
     * Google DriveにGoogle形式の新規ファイルを作成する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $name  表示名
     * @param  string  $type  作成するGoogle Drive項目の種類
     * @param  ?string  $parentId  保存先フォルダID
     * @return GoogleDriveFile Google APIが返した作成後のGoogle形式ファイル情報
     *
     * @throws GoogleDriveResponseException 作成種別が不正な場合
     */
    public function createGoogleFile(
        User $user,
        string $name,
        string $type,
        ?string $parentId,
    ): GoogleDriveFile {
        $mimeType = match ($type) {
            'document' => 'application/vnd.google-apps.document',
            'spreadsheet' => 'application/vnd.google-apps.spreadsheet',
            'presentation' => 'application/vnd.google-apps.presentation',
            default => throw new GoogleDriveResponseException('作成するGoogleファイル種別が不正です。'),
        };

        return $this->createMetadata($user, [
            'name' => trim($name),
            'mimeType' => $mimeType,
            'parents' => [$this->resource->parentId($parentId)],
        ]);
    }

    /**
     * アップロードされたファイルをGoogle Driveへ保存する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  UploadedFile  $uploadedFile  アップロードされたファイル
     * @param  ?string  $parentId  保存先フォルダID
     * @return GoogleDriveFile Google APIが返したアップロード後のファイル情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Drive APIがエラーを返した場合
     * @throws GoogleDriveResponseException ファイルを読み込めないかAPI応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function upload(User $user, UploadedFile $uploadedFile, ?string $parentId): GoogleDriveFile
    {
        $stream = fopen($uploadedFile->getRealPath(), 'rb');

        if ($stream === false) {
            throw new GoogleDriveResponseException('アップロードファイルを読み込めません。');
        }

        $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
        $metadata = [
            'name' => $uploadedFile->getClientOriginalName(),
            'parents' => [$this->resource->parentId($parentId)],
        ];
        $bodyStream = Utils::streamFor($stream);
        $scopes = $this->configuration->requiredScopes();

        try {
            $accessToken = $this->client->accessToken($user, $scopes);
            $sessionUrl = $this->createUploadSession($accessToken, $metadata, $mimeType);
            $response = $this->client->request($accessToken)
                ->withBody($bodyStream, $mimeType)
                ->put($sessionUrl);

            // 本体送信中に認証だけ切れた場合は、同じ再開URLへ一度だけ再送する。
            if ($response->status() === 401) {
                $bodyStream->rewind();
                $accessToken = $this->client->refreshAccessToken($user, $scopes);
                $response = $this->client->request($accessToken)
                    ->withBody($bodyStream, $mimeType)
                    ->put($sessionUrl);
            }

            $response->throw();
            $file = $response->json();
        } finally {
            fclose($stream);
        }

        if (! is_array($file ?? null)) {
            throw new GoogleDriveResponseException('Google Drive API応答にアップロード結果がありません。');
        }

        // 再開可能アップロードは共通sendを通らないため、成功後にDrive一覧を明示的に更新対象とする。
        $this->cache->invalidate($user, 'drive');

        return $this->mapper->file($file);
    }

    /**
     * Google Driveのファイル情報を更新する。
     *
     * @param  array  $attributes  検証済みの名称・説明・スター・移動先設定
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     *
     * @phpstan-param array{
     *     name?: string,
     *     description?: string|null,
     *     starred?: bool,
     *     parent_id?: string|null
     * } $attributes
     *
     * @return GoogleDriveFile Google APIが返した更新後のファイル情報
     *
     * @throws GoogleDriveResponseException 入力・ファイルID・API応答が不正な場合
     */
    public function updateFile(User $user, string $fileId, array $attributes): GoogleDriveFile
    {
        $current = $this->queries->file($user, $fileId);
        $body = [];
        $query = ['fields' => GoogleDriveApiContract::FILE_FIELDS, 'supportsAllDrives' => true];

        foreach (['name', 'description', 'starred'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $body[$key] = $attributes[$key];
            }
        }

        if (array_key_exists('parent_id', $attributes)) {
            $query['addParents'] = $this->resource->parentId($attributes['parent_id']);

            if ($current->parents !== []) {
                $query['removeParents'] = implode(',', $current->parents);
            }
        }

        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'PATCH',
            $this->resource->fileUrl($fileId),
            ['query' => $query, 'json' => $body],
        );
        $file = $response->json();

        if (! is_array($file)) {
            throw new GoogleDriveResponseException('Google Drive API応答に更新結果がありません。');
        }

        return $this->mapper->file($file);
    }

    /**
     * Google Driveのファイルを複製する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  ?string  $name  表示名
     * @param  ?string  $parentId  保存先フォルダID
     * @return GoogleDriveFile Google APIが返した複製後のファイル情報
     *
     * @throws GoogleDriveResponseException ファイルID・保存先・API応答が不正な場合
     */
    public function copy(User $user, string $fileId, ?string $name, ?string $parentId): GoogleDriveFile
    {
        $body = ['parents' => [$this->resource->parentId($parentId)]];

        if ($name !== null && trim($name) !== '') {
            $body['name'] = trim($name);
        }

        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->resource->fileUrl($fileId).'/copy',
            [
                'query' => ['fields' => GoogleDriveApiContract::FILE_FIELDS, 'supportsAllDrives' => true],
                'json' => $body,
            ],
        );
        $file = $response->json();

        if (! is_array($file)) {
            throw new GoogleDriveResponseException('Google Drive API応答に複製結果がありません。');
        }

        return $this->mapper->file($file);
    }

    /**
     * Google Driveのファイルをゴミ箱へ移動する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     */
    public function trash(User $user, string $fileId): void
    {
        $this->setTrashed($user, $fileId, true);
    }

    /**
     * Google Driveのファイルをゴミ箱から復元する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     */
    public function restore(User $user, string $fileId): void
    {
        $this->setTrashed($user, $fileId, false);
    }

    /**
     * Google側の対象リソースを完全に削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     */
    public function delete(User $user, string $fileId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->resource->fileUrl($fileId),
            ['query' => ['supportsAllDrives' => true]],
        );
    }

    /**
     * Google Driveへ作成するファイルメタデータを登録する。
     *
     * @param  array<string, mixed>  $metadata  Google Driveへ登録するファイルメタデータ
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @return GoogleDriveFile Google APIが返した作成後のファイル情報
     *
     * @throws GoogleDriveResponseException API応答が不正な場合
     */
    private function createMetadata(User $user, array $metadata): GoogleDriveFile
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->configuration->filesUri(),
            [
                'query' => ['fields' => GoogleDriveApiContract::FILE_FIELDS, 'supportsAllDrives' => true],
                'json' => $metadata,
            ],
        );
        $file = $response->json();

        if (! is_array($file)) {
            throw new GoogleDriveResponseException('Google Drive API応答に作成結果がありません。');
        }

        return $this->mapper->file($file);
    }

    /**
     * 大容量アップロード用の再開可能セッションを開始する。
     *
     * @param  string  $accessToken  Google APIアクセストークン
     * @param  array{name: string, parents: list<string>}  $metadata  作成するファイルメタデータ
     * @param  string  $mimeType  アップロード対象のMIMEタイプ
     * @return string 再開可能アップロードURL
     *
     * @throws GoogleDriveResponseException アップロード再開URLを取得できない場合
     */
    private function createUploadSession(
        #[\SensitiveParameter] string $accessToken,
        array $metadata,
        string $mimeType,
    ): string {
        $url = $this->configuration->uploadUri().'?'.http_build_query([
            'fields' => GoogleDriveApiContract::FILE_FIELDS,
            'supportsAllDrives' => 'true',
            'uploadType' => 'resumable',
        ], '', '&', PHP_QUERY_RFC3986);
        $response = $this->client->request($accessToken)
            ->withHeaders(['X-Upload-Content-Type' => $mimeType])
            ->post($url, $metadata);
        $response->throw();
        $location = $response->header('Location');

        if ($location === '') {
            throw new GoogleDriveResponseException('Google Drive APIからアップロードURLが返されませんでした。');
        }

        return $location;
    }

    /**
     * Google Driveファイルのゴミ箱状態を変更する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  bool  $trashed  ゴミ箱へ移動する場合はtrue、復元する場合はfalse
     */
    private function setTrashed(User $user, string $fileId, bool $trashed): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'PATCH',
            $this->resource->fileUrl($fileId),
            [
                'query' => ['fields' => 'id,trashed', 'supportsAllDrives' => true],
                'json' => ['trashed' => $trashed],
            ],
        );
    }
}
