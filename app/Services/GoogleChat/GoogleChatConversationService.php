<?php

namespace App\Services\GoogleChat;

use App\Data\GoogleChatConversation;
use App\Data\GoogleChatMembership;
use App\Data\GoogleChatMessage;
use App\Data\GoogleChatSpace;
use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Models\User;
use App\Services\GoogleChat\Support\GoogleChatApiContract;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleChat\Support\GoogleChatMapper;
use App\Services\GoogleChat\Support\GoogleChatResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * Google Chatのスペース一覧と会話詳細を必要な範囲だけ取得する。
 */
final class GoogleChatConversationService
{
    private const SPACE_CACHE_SECONDS = 300;

    private const SPACE_DETAIL_CACHE_SECONDS = 180;

    private const MEMBERSHIP_CACHE_SECONDS = 180;

    private const MESSAGE_CACHE_SECONDS = 60;

    private const PIN_CACHE_SECONDS = 60;

    /**
     * Chat APIの選択実行とキャッシュ分離に必要な依存を受け取る。
     *
     * @param  GoogleApiClient  $client  認証・再試行・並列通信を共通化したGoogle APIクライアント
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleChatResource  $resource  ChatリソースID検証
     * @param  GoogleChatMapper  $mapper  Chat API応答のDTO変換
     * @param  GoogleWorkspaceCache  $cache  利用者単位のSWRキャッシュ
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleChatConfiguration $configuration,
        private readonly GoogleChatResource $resource,
        private readonly GoogleChatMapper $mapper,
        private readonly GoogleWorkspaceCache $cache,
    ) {}

    /**
     * 認証ユーザーが参加しているGoogle Chatスペース一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @return Collection<int, GoogleChatSpace> 参加中スペース一覧
     */
    public function spaces(User $user): Collection
    {
        return $this->cache->remember(
            $user,
            'chat',
            'spaces',
            self::SPACE_CACHE_SECONDS,
            function () use ($user): Collection {
                $response = $this->client->send(
                    $user,
                    $this->configuration->requiredScopes(),
                    'GET',
                    $this->configuration->apiUrl('/spaces'),
                    ['query' => [
                        'fields' => GoogleChatApiContract::SPACE_LIST_FIELDS,
                        'pageSize' => $this->configuration->spacePageSize(),
                    ]],
                );

                return $this->spacesFromResponse($response);
            },
        );
    }

    /**
     * 選択スペースの詳細、メッセージ、固定表示と必要時だけメンバーを取得する。
     * メンバーパネルを閉じている通常表示ではmembers.listを呼ばず、会話遷移のAPI待ちを減らす。
     * キャッシュされていない項目だけを同時送信する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  表示対象Google ChatスペースID
     * @param  bool  $includeMemberships  メンバーパネル用一覧を取得する場合はtrue
     * @return GoogleChatConversation Chat会話画面へ渡す情報一式
     */
    public function conversation(
        User $user,
        string $spaceId,
        bool $includeMemberships = false,
    ): GoogleChatConversation {
        $validatedSpaceId = $this->resource->validatedId($spaceId);
        $spaceName = $this->resource->spaceName($validatedSpaceId);
        $spaceUrl = $this->configuration->apiUrl('/'.$spaceName);
        $spaces = $this->cache->get($user, 'chat', 'spaces');
        $space = $this->cache->get($user, 'chat', 'space:'.$validatedSpaceId);
        $memberships = $includeMemberships
            ? $this->cache->get($user, 'chat', 'memberships:'.$validatedSpaceId)
            : collect();
        $messages = $this->cache->get($user, 'chat', 'messages:'.$validatedSpaceId);
        $pinnedMessageNames = $this->cache->get($user, 'chat', 'pins:'.$validatedSpaceId);
        $requests = [];

        if (! $spaces instanceof Collection) {
            $requests['spaces'] = [
                'method' => 'GET',
                'url' => $this->configuration->apiUrl('/spaces'),
                'options' => ['query' => [
                    'fields' => GoogleChatApiContract::SPACE_LIST_FIELDS,
                    'pageSize' => $this->configuration->spacePageSize(),
                ]],
            ];
        }

        if (! $space instanceof GoogleChatSpace) {
            $requests['space'] = [
                'method' => 'GET',
                'url' => $spaceUrl,
                'options' => ['query' => [
                    'fields' => GoogleChatApiContract::SPACE_DETAIL_FIELDS,
                ]],
            ];
        }

        if ($includeMemberships && ! $memberships instanceof Collection) {
            $requests['memberships'] = [
                'method' => 'GET',
                'url' => $spaceUrl.'/members',
                'options' => ['query' => [
                    'fields' => GoogleChatApiContract::MEMBERSHIP_FIELDS,
                    'pageSize' => 1000,
                    'showGroups' => true,
                    'showInvited' => true,
                ]],
            ];
        }

        if (! $messages instanceof Collection) {
            $requests['messages'] = [
                'method' => 'GET',
                'url' => $spaceUrl.'/messages',
                'options' => ['query' => [
                    'fields' => GoogleChatApiContract::MESSAGE_FIELDS,
                    'orderBy' => 'createTime DESC',
                    'pageSize' => $this->configuration->messagePageSize(),
                    'showDeleted' => true,
                ]],
            ];
        }

        if (! is_array($pinnedMessageNames)) {
            $requests['pins'] = [
                'method' => 'GET',
                'url' => $spaceUrl.'/messagePins',
                'options' => ['query' => [
                    'fields' => 'messagePins(message),nextPageToken',
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

            if (isset($responses['spaces'])) {
                $spaces = $this->spacesFromResponse($responses['spaces']);
                $this->cache->put($user, 'chat', 'spaces', self::SPACE_CACHE_SECONDS, $spaces);
            }

            if (isset($responses['space'])) {
                $spacePayload = $responses['space']->json();

                if (! is_array($spacePayload)) {
                    throw new GoogleChatResponseException('Google Chat API応答にスペース情報がありません。');
                }

                $space = $this->mapper->space($spacePayload);
                $this->cache->put(
                    $user,
                    'chat',
                    'space:'.$validatedSpaceId,
                    self::SPACE_DETAIL_CACHE_SECONDS,
                    $space,
                );
            }

            if (isset($responses['memberships'])) {
                $memberships = $this->memberships($responses['memberships']);
                $this->cache->put(
                    $user,
                    'chat',
                    'memberships:'.$validatedSpaceId,
                    self::MEMBERSHIP_CACHE_SECONDS,
                    $memberships,
                );
            }

            if (isset($responses['messages'])) {
                $messages = $this->messages($responses['messages']);
                $this->cache->put(
                    $user,
                    'chat',
                    'messages:'.$validatedSpaceId,
                    self::MESSAGE_CACHE_SECONDS,
                    $messages,
                );
            }

            if (isset($responses['pins'])) {
                $pinnedMessageNames = $this->pinnedMessageNames($responses['pins']);
                $this->cache->put(
                    $user,
                    'chat',
                    'pins:'.$validatedSpaceId,
                    self::PIN_CACHE_SECONDS,
                    $pinnedMessageNames,
                );
            }
        }

        $normalizedPinnedMessageNames = is_array($pinnedMessageNames)
            ? array_values(array_filter(
                $pinnedMessageNames,
                static fn (mixed $messageName): bool => is_string($messageName),
            ))
            : [];

        return new GoogleChatConversation(
            spaces: $spaces instanceof Collection ? $spaces : collect(),
            space: $space instanceof GoogleChatSpace
                ? $space
                : throw new GoogleChatResponseException('Google Chat API応答にスペース情報がありません。'),
            memberships: $memberships instanceof Collection ? $memberships : collect(),
            messages: $messages instanceof Collection ? $messages : collect(),
            pinnedMessageNames: $normalizedPinnedMessageNames,
        );
    }

    /**
     * spaces.list応答を画面表示用スペース一覧へ変換する。
     *
     * @param  Response  $response  Google Chat spaces.list応答
     * @return Collection<int, GoogleChatSpace> 参加中スペース一覧
     */
    private function spacesFromResponse(Response $response): Collection
    {
        $spaces = $response->json('spaces');

        if ($spaces === null) {
            return collect();
        }

        if (! is_array($spaces)) {
            throw new GoogleChatResponseException('Google Chat API応答にスペース一覧がありません。');
        }

        return collect($spaces)
            ->filter(static fn (mixed $space): bool => is_array($space))
            ->map(fn (array $space): GoogleChatSpace => $this->mapper->space($space))
            ->values();
    }

    /**
     * spaces.members.list応答をメンバー一覧へ変換する。
     *
     * @param  Response  $response  Google Chat spaces.members.list応答
     * @return Collection<int, GoogleChatMembership> スペースメンバー一覧
     */
    private function memberships(Response $response): Collection
    {
        $memberships = $response->json('memberships');

        if ($memberships === null) {
            return collect();
        }

        if (! is_array($memberships)) {
            throw new GoogleChatResponseException('Google Chat API応答にメンバー一覧がありません。');
        }

        return collect($memberships)
            ->filter(static fn (mixed $membership): bool => is_array($membership))
            ->map(fn (array $membership): GoogleChatMembership => $this->mapper->membership($membership))
            ->values();
    }

    /**
     * spaces.messages.list応答をメッセージ一覧へ変換する。
     *
     * @param  Response  $response  Google Chat spaces.messages.list応答
     * @return Collection<int, GoogleChatMessage> 新しい順のメッセージ一覧
     */
    private function messages(Response $response): Collection
    {
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
     * spaces.messagePins.list応答から固定メッセージのリソース名だけを抽出する。
     *
     * @param  Response  $response  Google Chat spaces.messagePins.list応答
     * @return list<string> 固定メッセージのリソース名
     */
    private function pinnedMessageNames(Response $response): array
    {
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
}
