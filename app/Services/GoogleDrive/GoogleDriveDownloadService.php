<?php

namespace App\Services\GoogleDrive;

use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use App\Models\User;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleDrive\Support\GoogleDriveResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;

/**
 * Google DriveファイルのダウンロードとGoogle形式のエクスポートを担当する。
 */
final class GoogleDriveDownloadService
{
    /**
     * 通常ファイルとGoogle形式ファイルの取得方法を一箇所で切り替えるため、参照・設定Serviceを注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleDriveConfiguration  $configuration  Drive APIのURI・スコープ・取得上限を提供する設定サービス
     * @param  GoogleDriveResource  $resource  GoogleリソースIDとURLを安全に扱うサービス
     * @param  GoogleDriveQueryService  $queries  Google Driveの参照処理を担当するサービス
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleDriveConfiguration $configuration,
        private readonly GoogleDriveResource $resource,
        private readonly GoogleDriveQueryService $queries,
    ) {}

    /**
     * Google Driveのファイルをダウンロード応答として取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @return array{body: string, content_type: string, filename: string} ダウンロード本文・Content-Type・ファイル名
     */
    public function download(User $user, string $fileId): array
    {
        [$url, $query, $contentType, $filename] = $this->downloadDefinition($user, $fileId);
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $url,
            ['query' => $query],
            acceptJson: false,
        );
        $responseType = $response->header('Content-Type');

        return [
            'body' => $response->body(),
            'content_type' => $responseType !== '' ? $responseType : $contentType,
            'filename' => $filename,
        ];
    }

    /**
     * Google Driveのファイルを指定したローカルパスへ保存する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @param  string  $path  ダウンロード内容を書き込むファイルの絶対パス
     * @return array{content_type: string, filename: string} 保存したファイルのContent-Typeとファイル名
     *
     * @throws GoogleDriveResponseException 保存先パスが不正な場合
     */
    public function downloadToPath(User $user, string $fileId, string $path): array
    {
        if ($path === '' || ! is_dir(dirname($path))) {
            throw new GoogleDriveResponseException('Google Driveファイルの保存先が不正です。');
        }

        [$url, $query, $contentType, $filename] = $this->downloadDefinition($user, $fileId);
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $url,
            ['query' => $query, 'sink' => $path],
            acceptJson: false,
        );
        $responseType = $response->header('Content-Type');

        return [
            'content_type' => $responseType !== '' ? $responseType : $contentType,
            'filename' => $filename,
        ];
    }

    /**
     * ファイル形式に応じたダウンロードURL・クエリ・応答形式・ファイル名を決定する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $fileId  Google DriveのファイルID
     * @return array{0: string, 1: array<string, mixed>, 2: string, 3: string} API URL・クエリ・Content-Type・ファイル名
     */
    private function downloadDefinition(User $user, string $fileId): array
    {
        $file = $this->queries->file($user, $fileId);
        $contentType = 'application/octet-stream';
        $filename = $file->name;
        $url = $this->resource->fileUrl($fileId);
        $query = ['alt' => 'media', 'supportsAllDrives' => true];

        if ($file->isGoogleWorkspaceFile()) {
            [$contentType, $extension] = $this->resource->exportFormat($file->mimeType);
            $filename = $this->resource->filenameWithExtension($file->name, $extension);
            $url .= '/export';
            $query = ['mimeType' => $contentType];
        }

        return [$url, $query, $contentType, $filename];
    }
}
