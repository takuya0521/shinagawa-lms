<?php

namespace App\Services\GoogleChat;

use App\Data\GoogleChatSpace;
use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleChat\Support\GoogleChatApiContract;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleChat\Support\GoogleChatMapper;
use App\Services\GoogleChat\Support\GoogleChatResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Google Chatのスペース取得・作成・更新・削除を担当する。
 * メンバーやメッセージ操作を別Serviceへ分離し、スペース自体のライフサイクルだけを
 * このクラスの責務とする。
 */
final class GoogleChatSpaceService
{
    /**
     * スペース操作で認証・リソース検証・応答変換の条件を統一するため、共通依存を注入する。
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
     * 認証ユーザーが参加しているGoogle Chatスペースを取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string|null  $pageToken  次ページ取得トークン
     * @return Collection<int, GoogleChatSpace> 画面表示用スペース一覧
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Chat APIがエラーを返した場合
     * @throws GoogleChatResponseException API応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function spaces(User $user, ?string $pageToken = null): Collection
    {
        $query = [
            'fields' => GoogleChatApiContract::SPACE_LIST_FIELDS,
            'pageSize' => $this->configuration->spacePageSize(),
        ];

        if ($pageToken !== null && $pageToken !== '') {
            $query['pageToken'] = mb_substr($pageToken, 0, 2048);
        }

        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->configuration->apiUrl('/spaces'),
            ['query' => $query],
        );
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
     * 指定されたスペースの詳細を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @return GoogleChatSpace 画面表示用スペース情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Chat APIがエラーを返した場合
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function space(User $user, string $spaceId): GoogleChatSpace
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->spaceUrl($spaceId),
            ['query' => ['fields' => GoogleChatApiContract::SPACE_DETAIL_FIELDS]],
        );
        $space = $response->json();

        if (! is_array($space)) {
            throw new GoogleChatResponseException('Google Chat API応答にスペース情報がありません。');
        }

        return $this->mapper->space($space);
    }

    /**
     * 名前付きスペース、グループチャット、ダイレクトメッセージを初期メンバー付きで作成する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceType  SPACE、GROUP_CHAT、DIRECT_MESSAGEのいずれか
     * @param  string  $displayName  名前付きスペースの表示名
     * @param  string|null  $description  名前付きスペースの説明
     * @param  list<string>  $memberEmails  自分以外の初期メンバー
     * @return GoogleChatSpace 作成または取得したChat会話
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Chat APIがエラーを返した場合
     * @throws GoogleChatResponseException API応答または入力が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function setupSpace(
        User $user,
        string $spaceType,
        string $displayName,
        ?string $description,
        array $memberEmails,
    ): GoogleChatSpace {
        $this->assertSetupInput($spaceType, $memberEmails);
        $space = ['spaceType' => $spaceType];

        if ($spaceType === 'SPACE') {
            $space['displayName'] = trim($displayName);
            $space['spaceDetails'] = ['description' => $description];
        } elseif ($spaceType === 'DIRECT_MESSAGE') {
            $space['singleUserBotDm'] = false;
        }

        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->configuration->apiUrl('/spaces:setup'),
            ['json' => [
                'space' => $space,
                'requestId' => (string) Str::uuid(),
                'memberships' => array_map(
                    static fn (string $email): array => [
                        'member' => [
                            'name' => 'users/'.$email,
                            'type' => 'HUMAN',
                        ],
                    ],
                    $memberEmails,
                ),
            ]],
        );
        $createdSpace = $response->json();

        if (! is_array($createdSpace)) {
            throw new GoogleChatResponseException('Google Chat API応答に会話作成結果がありません。');
        }

        return $this->mapper->space($createdSpace);
    }

    /**
     * 名前付きGoogle Chatスペースを作成する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $displayName  スペース名
     * @param  string|null  $description  スペース説明
     * @return GoogleChatSpace 作成したスペース
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Chat APIがエラーを返した場合
     * @throws GoogleChatResponseException API応答または設定が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function createSpace(User $user, string $displayName, ?string $description): GoogleChatSpace
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->configuration->apiUrl('/spaces'),
            [
                'query' => ['requestId' => (string) Str::uuid()],
                'json' => [
                    'spaceType' => 'SPACE',
                    'displayName' => trim($displayName),
                    'spaceDetails' => ['description' => $description],
                ],
            ],
        );
        $space = $response->json();

        if (! is_array($space)) {
            throw new GoogleChatResponseException('Google Chat API応答にスペース作成結果がありません。');
        }

        return $this->mapper->space($space);
    }

    /**
     * 名前付きスペースの名称と説明を更新する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $displayName  スペース名
     * @param  string|null  $description  スペース説明
     * @return GoogleChatSpace 更新後のスペース
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Chat APIがエラーを返した場合
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function updateSpace(
        User $user,
        string $spaceId,
        string $displayName,
        ?string $description,
    ): GoogleChatSpace {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'PATCH',
            $this->spaceUrl($spaceId),
            [
                'query' => ['updateMask' => 'displayName,spaceDetails'],
                'json' => [
                    'displayName' => trim($displayName),
                    'spaceDetails' => ['description' => $description],
                ],
            ],
        );
        $space = $response->json();

        if (! is_array($space)) {
            throw new GoogleChatResponseException('Google Chat API応答にスペース更新結果がありません。');
        }

        return $this->mapper->space($space);
    }

    /**
     * 名前付きスペースと配下のメッセージ・メンバーを完全削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Chat APIがエラーを返した場合
     * @throws GoogleChatResponseException IDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function deleteSpace(User $user, string $spaceId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->spaceUrl($spaceId),
        );
    }

    /**
     * 会話種別と初期メンバー数がGoogle Chat APIの条件を満たすか確認する。
     *
     * @param  string  $spaceType  Google Chat会話種別
     * @param  list<string>  $memberEmails  初期メンバーのメールアドレス
     *
     * @throws GoogleChatResponseException 入力条件を満たさない場合
     */
    private function assertSetupInput(string $spaceType, array $memberEmails): void
    {
        if (! in_array($spaceType, ['SPACE', 'GROUP_CHAT', 'DIRECT_MESSAGE'], true)) {
            throw new GoogleChatResponseException('Google Chatの会話種別が不正です。');
        }

        if ($spaceType === 'DIRECT_MESSAGE' && count($memberEmails) !== 1) {
            throw new GoogleChatResponseException('ダイレクトメッセージには相手を1件指定してください。');
        }

        if ($spaceType === 'GROUP_CHAT' && count($memberEmails) < 2) {
            throw new GoogleChatResponseException('グループチャットには自分以外のメンバーを2件以上指定してください。');
        }

        if (count($memberEmails) > 49) {
            throw new GoogleChatResponseException('初期メンバーは49件以内で指定してください。');
        }
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
