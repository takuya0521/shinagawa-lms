<?php

namespace App\Services\GoogleChat;

use App\Data\GoogleChatMembership;
use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Models\User;
use App\Services\GoogleChat\Support\GoogleChatApiContract;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleChat\Support\GoogleChatMapper;
use App\Services\GoogleChat\Support\GoogleChatResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Support\Collection;

/**
 * Google Chatスペースのメンバー取得・追加・削除を担当する。
 */
final class GoogleChatMembershipService
{
    /**
     * メンバー操作で認証・リソース検証・応答変換の条件を統一するため、共通依存を注入する。
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
     * 指定スペースのメンバー一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @return Collection<int, GoogleChatMembership> メンバー一覧
     *
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     */
    public function memberships(User $user, string $spaceId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->spaceUrl($spaceId).'/members',
            ['query' => [
                'fields' => GoogleChatApiContract::MEMBERSHIP_FIELDS,
                'pageSize' => 1000,
                'showGroups' => true,
                'showInvited' => true,
            ]],
        );
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
     * メールアドレスで指定したGoogleユーザーをスペースへ招待または追加する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $email  追加対象Googleアカウント
     * @return GoogleChatMembership 作成したメンバーシップ
     *
     * @throws GoogleChatResponseException API応答またはIDが不正な場合
     */
    public function addMember(User $user, string $spaceId, string $email): GoogleChatMembership
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->spaceUrl($spaceId).'/members',
            ['json' => [
                'member' => [
                    'name' => 'users/'.trim($email),
                    'type' => 'HUMAN',
                ],
            ]],
        );
        $membership = $response->json();

        if (! is_array($membership)) {
            throw new GoogleChatResponseException('Google Chat API応答にメンバー追加結果がありません。');
        }

        return $this->mapper->membership($membership);
    }

    /**
     * 指定メンバーをスペースから削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $membershipId  メンバーシップID
     */
    public function removeMember(User $user, string $spaceId, string $membershipId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->spaceUrl($spaceId).'/members/'.$this->resource->validatedId($membershipId),
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
