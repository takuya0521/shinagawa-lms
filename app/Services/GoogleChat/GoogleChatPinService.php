<?php

namespace App\Services\GoogleChat;

use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Models\User;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleChat\Support\GoogleChatResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;

/**
 * Google Chatメッセージの固定表示取得・設定・解除を担当する。
 */
final class GoogleChatPinService
{
    /**
     * 固定表示操作で認証とリソース検証の条件を統一するため、共通依存を注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleChatResource  $resource  Chatリソース名の検証担当
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleChatConfiguration $configuration,
        private readonly GoogleChatResource $resource,
    ) {}

    /**
     * 指定スペースで固定表示されているメッセージリソース名を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @return list<string> 固定メッセージのリソース名
     *
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     */
    public function pinnedMessageNames(User $user, string $spaceId): array
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->spaceUrl($spaceId).'/messagePins',
            ['query' => ['pageSize' => 100]],
        );
        $pins = $response->json('messagePins');

        if ($pins === null) {
            return [];
        }

        if (! is_array($pins)) {
            throw new GoogleChatResponseException('Google Chat API応答に固定メッセージ一覧がありません。');
        }

        return array_values(array_filter(array_map(
            static function (mixed $pin): ?string {
                if (! is_array($pin)) {
                    return null;
                }

                $message = $pin['message'] ?? null;

                return is_string($message) && $message !== '' ? $message : null;
            },
            $pins,
        )));
    }

    /**
     * 指定メッセージをスペースへ固定表示する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     */
    public function pinMessage(User $user, string $spaceId, string $messageId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->spaceUrl($spaceId).'/messagePins',
            ['json' => ['message' => $this->resource->messageName($spaceId, $messageId)]],
        );
    }

    /**
     * 指定メッセージの固定表示を解除する。
     * MessagePinのIDはメッセージIDと同一であるため、画面から受け取るIDを共通検証して利用する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     */
    public function unpinMessage(User $user, string $spaceId, string $messageId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->spaceUrl($spaceId).'/messagePins/'.$this->resource->validatedId($messageId),
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
}
