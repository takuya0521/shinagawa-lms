<?php

namespace App\Services\GoogleChat;

use App\Data\GoogleChatMessage;
use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Models\User;
use App\Services\GoogleChat\Support\GoogleChatApiContract;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleChat\Support\GoogleChatMapper;
use App\Services\GoogleChat\Support\GoogleChatResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Google Chatメッセージの取得・投稿・更新・削除を担当する。
 */
final class GoogleChatMessageService
{
    /**
     * メッセージ操作で認証・添付処理・応答変換を連携させるため、関連Serviceを注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleChatResource  $resource  Chatリソース名の検証担当
     * @param  GoogleChatMapper  $mapper  API応答のDTO変換担当
     * @param  GoogleChatAttachmentService  $attachmentService  添付アップロード担当
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleChatConfiguration $configuration,
        private readonly GoogleChatResource $resource,
        private readonly GoogleChatMapper $mapper,
        private readonly GoogleChatAttachmentService $attachmentService,
    ) {}

    /**
     * 指定スペースのメッセージを新しい順で取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @return Collection<int, GoogleChatMessage> メッセージ一覧
     *
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     */
    public function messages(User $user, string $spaceId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->spaceUrl($spaceId).'/messages',
            ['query' => [
                'fields' => GoogleChatApiContract::MESSAGE_FIELDS,
                'orderBy' => 'createTime DESC',
                'pageSize' => $this->configuration->messagePageSize(),
                'showDeleted' => true,
            ]],
        );
        $messages = $response->json('messages');

        if ($messages === null) {
            return collect();
        }

        if (! is_array($messages)) {
            throw new GoogleChatResponseException('Google Chat API応答にメッセージ一覧がありません。');
        }

        return collect($messages)
            ->filter(static fn (mixed $message): bool => is_array($message))
            ->map(fn (array $message): GoogleChatMessage => $this->mapper->message($message))
            ->values();
    }

    /**
     * 指定スペースへメッセージを投稿し、必要に応じてスレッドへ返信する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $text  メッセージ本文
     * @param  string|null  $threadName  返信先スレッドリソース名
     * @param  UploadedFile|null  $attachment  添付ファイル
     * @return GoogleChatMessage 作成したメッセージ
     *
     * @throws GoogleChatResponseException API応答、IDまたは添付が不正な場合
     */
    public function createMessage(
        User $user,
        string $spaceId,
        string $text,
        ?string $threadName = null,
        ?UploadedFile $attachment = null,
    ): GoogleChatMessage {
        $body = ['text' => trim($text)];

        if ($threadName !== null && $threadName !== '') {
            $body['thread'] = ['name' => $this->resource->validatedThreadName($spaceId, $threadName)];
        }

        if ($attachment instanceof UploadedFile) {
            $body['attachment'] = [[
                'attachmentDataRef' => $this->attachmentService->upload($user, $spaceId, $attachment),
            ]];
        }

        $query = [];
        if ($threadName !== null && $threadName !== '') {
            $query['messageReplyOption'] = 'REPLY_MESSAGE_FALLBACK_TO_NEW_THREAD';
        }

        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->spaceUrl($spaceId).'/messages',
            ['query' => $query, 'json' => $body],
        );
        $message = $response->json();

        if (! is_array($message)) {
            throw new GoogleChatResponseException('Google Chat API応答にメッセージ作成結果がありません。');
        }

        return $this->mapper->message($message);
    }

    /**
     * 自分が投稿したGoogle Chatメッセージ本文を更新する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  string  $text  更新後本文
     * @return GoogleChatMessage 更新後メッセージ
     *
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     */
    public function updateMessage(
        User $user,
        string $spaceId,
        string $messageId,
        string $text,
    ): GoogleChatMessage {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'PATCH',
            $this->messageUrl($spaceId, $messageId),
            [
                'query' => ['updateMask' => 'text'],
                'json' => ['text' => trim($text)],
            ],
        );
        $message = $response->json();

        if (! is_array($message)) {
            throw new GoogleChatResponseException('Google Chat API応答にメッセージ更新結果がありません。');
        }

        return $this->mapper->message($message);
    }

    /**
     * 自分が投稿したメッセージを削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  bool  $force  スレッド返信も含めて削除するかどうか
     */
    public function deleteMessage(
        User $user,
        string $spaceId,
        string $messageId,
        bool $force = false,
    ): void {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->messageUrl($spaceId, $messageId),
            ['query' => ['force' => $force]],
        );
    }

    /**
     * 検証済みスペースIDからAPI URLを生成する。
     *
     * @param  string  $spaceId  Google ChatスペースID
     * @return string スペースAPI URL
     */
    private function spaceUrl(string $spaceId): string
    {
        return $this->configuration->apiUrl('/'.$this->resource->spaceName($spaceId));
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
}
