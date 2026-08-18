<?php

namespace App\Services\GoogleChat;

use App\Data\GoogleChatReaction;
use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Models\User;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleChat\Support\GoogleChatMapper;
use App\Services\GoogleChat\Support\GoogleChatResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Support\Collection;

/**
 * Google Chatメッセージのリアクション取得・追加・削除を担当する。
 */
final class GoogleChatReactionService
{
    /**
     * リアクション操作で認証・リソース検証・応答変換の条件を統一するため、共通依存を注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleChatResource  $resource  Chatリソース名の検証担当
     * @param  GoogleChatMapper  $mapper  API応答のDTO変換担当
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleChatConfiguration $configuration,
        private readonly GoogleChatResource $resource,
        private readonly GoogleChatMapper $mapper,
    ) {}

    /**
     * 指定メッセージへ付けられた個別リアクションを取得する。
     * メッセージ一覧の集計値だけでは削除対象リソース名が分からないため、利用者が
     * リアクション管理を開いた時だけ個別一覧を取得し、通常表示のAPI回数を抑える。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @return Collection<int, GoogleChatReaction> 個別リアクション一覧
     *
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     */
    public function reactions(User $user, string $spaceId, string $messageId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->messageUrl($spaceId, $messageId).'/reactions',
            ['query' => [
                'fields' => 'reactions(name,user(name,displayName),emoji)',
                'pageSize' => 100,
            ]],
        );
        $reactions = $response->json('reactions');

        if ($reactions === null) {
            return collect();
        }

        if (! is_array($reactions)) {
            throw new GoogleChatResponseException('Google Chat API応答にリアクション一覧がありません。');
        }

        return collect($reactions)
            ->filter(static fn (mixed $reaction): bool => is_array($reaction))
            ->map(fn (array $reaction): GoogleChatReaction => $this->mapper->reaction($reaction))
            ->values();
    }

    /**
     * メッセージへUnicode絵文字のリアクションを追加する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  string  $emoji  Unicode絵文字
     */
    public function addReaction(User $user, string $spaceId, string $messageId, string $emoji): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->messageUrl($spaceId, $messageId).'/reactions',
            ['json' => ['emoji' => ['unicode' => trim($emoji)]]],
        );
    }

    /**
     * 指定リアクションを削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  string  $reactionId  リアクションID
     */
    public function removeReaction(
        User $user,
        string $spaceId,
        string $messageId,
        string $reactionId,
    ): void {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->messageUrl($spaceId, $messageId).'/reactions/'.$this->resource->validatedId($reactionId),
        );
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
