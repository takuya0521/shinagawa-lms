<?php

namespace App\Services\GoogleChat;

use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Exceptions\GoogleDrive\GoogleDriveException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleChat\Support\GoogleChatApiContract;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleChat\Support\GoogleChatMapper;
use App\Services\GoogleChat\Support\GoogleChatResource;
use App\Services\GoogleDrive\GoogleDriveDownloadService;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;

/**
 * Google Chatメッセージの添付アップロードとダウンロードを担当する。
 * ストリーム処理、一時ファイル管理、Drive添付との切り替えはメッセージ本文操作と
 * 異なる失敗条件を持つため、専用Serviceへ分離する。
 */
final class GoogleChatAttachmentService
{
    /**
     * 添付の送受信とDrive添付取得で認証・リソース検証を共有するため、関連Serviceを注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleChatResource  $resource  Chatリソース名の検証担当
     * @param  GoogleChatMapper  $mapper  API応答の安全な文字列取得担当
     * @param  GoogleDriveDownloadService  $driveDownloadService  Drive添付の取得担当
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleChatConfiguration $configuration,
        private readonly GoogleChatResource $resource,
        private readonly GoogleChatMapper $mapper,
        private readonly GoogleDriveDownloadService $driveDownloadService,
    ) {}

    /**
     * Chat添付ファイルをアップロードし、メッセージ作成用参照情報を返す。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  UploadedFile  $file  検証済みアップロードファイル
     * @return array{resourceName?: string, attachmentUploadToken?: string} Chat添付参照
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Chat APIがエラーを返した場合
     * @throws GoogleChatResponseException ファイルまたはAPI応答が不正な場合
     * @throws GoogleWorkspaceException 共通OAuthの再連携または設定確認が必要な場合
     */
    public function upload(User $user, string $spaceId, UploadedFile $file): array
    {
        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new GoogleChatResponseException('Google Chatへ送る添付ファイルを読み込めません。');
        }

        try {
            $requiredScopes = $this->configuration->requiredScopes();
            $accessToken = $this->client->accessToken($user, $requiredScopes);
            $url = $this->configuration->uploadUrl(
                '/'.$this->resource->spaceName($spaceId).'/attachments:upload?uploadType=multipart',
            );
            $response = $this->sendUpload(
                $accessToken,
                $url,
                $stream,
                $file->getClientOriginalName(),
                $file->getMimeType() ?: 'application/octet-stream',
            );

            // 添付本体の送信中に認証だけ切れた場合は、ストリームを先頭へ戻して一度だけ再送する。
            if ($response->status() === 401) {
                rewind($stream);
                $accessToken = $this->client->refreshAccessToken($user, $requiredScopes);
                $response = $this->sendUpload(
                    $accessToken,
                    $url,
                    $stream,
                    $file->getClientOriginalName(),
                    $file->getMimeType() ?: 'application/octet-stream',
                );
            }

            $response->throw();
            $dataRef = $response->json('attachmentDataRef');

            if (! is_array($dataRef)) {
                throw new GoogleChatResponseException('Google Chat API応答に添付参照がありません。');
            }

            /**
             * @var array{resourceName?: string, attachmentUploadToken?: string} $dataRef
             */
            return $dataRef;
        } finally {
            fclose($stream);
        }
    }

    /**
     * Chatメッセージ内の添付情報を確認し、認証付きで一時ファイルへダウンロードする。
     * Attachment.getはChatアプリ認証専用のため、ユーザー認証で取得できるMessage.getから
     * 添付参照を特定する。Chatへ直接アップロードされたファイルはMedia API、Drive添付は
     * Drive APIを使い分け、Googleが定めるデータ参照方式に従う。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  string  $attachmentId  Google Chat添付ID
     * @return array{path: string, filename: string, content_type: string} ダウンロード用一時ファイル情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google ChatまたはDrive APIがエラーを返した場合
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     * @throws GoogleWorkspaceException 共通OAuthの再連携または設定確認が必要な場合
     */
    public function download(
        User $user,
        string $spaceId,
        string $messageId,
        string $attachmentId,
    ): array {
        $validatedAttachmentId = $this->resource->validatedId($attachmentId);
        $messageResponse = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->messageUrl($spaceId, $messageId),
            ['query' => ['fields' => GoogleChatApiContract::MESSAGE_ATTACHMENT_FIELDS]],
        );
        $attachments = $messageResponse->json('attachment');

        if (! is_array($attachments)) {
            throw new GoogleChatResponseException('Google Chatメッセージに添付情報がありません。');
        }

        $attachment = collect($attachments)->first(
            static fn (mixed $item): bool => is_array($item)
                && is_string($item['name'] ?? null)
                && str_ends_with($item['name'], '/attachments/'.$validatedAttachmentId),
        );

        if (! is_array($attachment)) {
            throw new GoogleChatResponseException('指定されたGoogle Chat添付が見つかりません。');
        }

        $path = $this->temporaryPath();
        $filename = $this->mapper->optionalString($attachment, 'contentName') ?? 'chat-attachment';
        $contentType = $this->mapper->optionalString($attachment, 'contentType') ?? 'application/octet-stream';
        $source = $this->mapper->optionalString($attachment, 'source');

        try {
            if ($source === 'DRIVE_FILE') {
                $driveDataRef = is_array($attachment['driveDataRef'] ?? null)
                    ? $attachment['driveDataRef']
                    : [];
                $driveFileId = $this->mapper->optionalString($driveDataRef, 'driveFileId');

                if ($driveFileId === null) {
                    throw new GoogleChatResponseException('Google Drive添付のファイルIDを取得できませんでした。');
                }

                $driveDownload = $this->driveDownloadService->downloadToPath($user, $driveFileId, $path);
                $filename = $driveDownload['filename'];
                $contentType = $driveDownload['content_type'];
            } else {
                $attachmentDataRef = is_array($attachment['attachmentDataRef'] ?? null)
                    ? $attachment['attachmentDataRef']
                    : [];
                $resourceName = $this->mapper->optionalString($attachmentDataRef, 'resourceName');

                if (
                    $resourceName === null
                    || ! preg_match(
                        '/\Aspaces\/[A-Za-z0-9._-]+\/attachments\/[A-Za-z0-9._-]+\z/',
                        $resourceName,
                    )
                ) {
                    throw new GoogleChatResponseException('Google Chat添付のメディア参照を取得できませんでした。');
                }

                $this->client->send(
                    $user,
                    $this->configuration->requiredScopes(),
                    'GET',
                    $this->configuration->apiUrl('/media/'.$resourceName),
                    ['query' => ['alt' => 'media'], 'sink' => $path],
                );
            }
        } catch (ConnectionException|RequestException|GoogleChatResponseException|GoogleWorkspaceException $exception) {
            @unlink($path);
            throw $exception;
        } catch (GoogleDriveException $exception) {
            @unlink($path);
            throw new GoogleChatResponseException(
                'Google Chat内のDrive添付を取得できませんでした。',
                previous: $exception,
            );
        }

        return [
            'path' => $path,
            'filename' => basename($filename),
            'content_type' => $contentType,
        ];
    }

    /**
     * Chatのメタデータとファイル本体をmultipart/related形式で送信する。
     *
     * @param  string  $accessToken  Google OAuthアクセストークン
     * @param  string  $url  ChatメディアアップロードURL
     * @param  resource  $stream  ファイルストリーム
     * @param  string  $filename  元ファイル名
     * @param  string  $mimeType  ファイルMIMEタイプ
     * @return Response Google Chat APIレスポンス
     *
     * @throws ConnectionException Googleへ接続できない場合
     */
    private function sendUpload(
        #[\SensitiveParameter] string $accessToken,
        string $url,
        $stream,
        string $filename,
        string $mimeType,
    ): Response {
        $multipart = new MultipartStream([
            [
                'name' => 'metadata',
                'contents' => json_encode(['filename' => $filename], JSON_THROW_ON_ERROR),
                'headers' => ['Content-Type' => 'application/json; charset=UTF-8'],
            ],
            [
                'name' => 'media',
                'contents' => Utils::streamFor($stream),
                'headers' => ['Content-Type' => $mimeType],
            ],
        ]);

        return $this->client->request($accessToken)
            ->withHeaders([
                'Content-Type' => 'multipart/related; boundary='.$multipart->getBoundary(),
            ])
            ->withBody($multipart, 'multipart/related')
            ->post($url);
    }

    /**
     * 検証済みスペース・メッセージIDからAPI URLを生成する。
     *
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @return string メッセージAPI URL
     */
    private function messageUrl(string $spaceId, string $messageId): string
    {
        return $this->configuration->apiUrl('/'.$this->resource->messageName($spaceId, $messageId));
    }

    /**
     * 添付ダウンロード用の権限制限された一時ファイルを作成する。
     *
     * @return string 作成した一時ファイルパス
     *
     * @throws GoogleChatResponseException 保存先または一時ファイルを作成できない場合
     */
    private function temporaryPath(): string
    {
        $directory = storage_path('app/private/google-chat');

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new GoogleChatResponseException('Google Chat添付の一時保存先を作成できませんでした。');
        }

        $path = tempnam($directory, 'attachment-');

        if (! is_string($path)) {
            throw new GoogleChatResponseException('Google Chat添付の一時ファイルを作成できませんでした。');
        }

        return $path;
    }
}
