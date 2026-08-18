<?php

namespace App\Services\GoogleCalendar;

use App\Data\GoogleCalendarAclRule;
use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleCalendar\Support\GoogleCalendarConfiguration;
use App\Services\GoogleCalendar\Support\GoogleCalendarMapper;
use App\Services\GoogleCalendar\Support\GoogleCalendarResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/**
 * Google Calendarの共有ルール取得・追加・削除を担当する。
 */
final class GoogleCalendarAclService
{
    /**
     * 共有ルール操作で認証・URL検証・応答変換の条件を統一するため、共通依存を注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleCalendarConfiguration  $configuration  Calendar APIのURI・スコープ・取得上限を提供する設定サービス
     * @param  GoogleCalendarResource  $resource  GoogleリソースIDとURLを安全に扱うサービス
     * @param  GoogleCalendarMapper  $mapper  Google API応答をDTOへ変換するサービス
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleCalendarConfiguration $configuration,
        private readonly GoogleCalendarResource $resource,
        private readonly GoogleCalendarMapper $mapper,
    ) {}

    /**
     * 指定カレンダーの共有権限一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @return Collection<int, GoogleCalendarAclRule> 指定カレンダーの共有ルール一覧
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException API応答またはカレンダーIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function aclRules(User $user, string $calendarId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->resource->calendarUrl($calendarId).'/acl',
            ['query' => ['maxResults' => 250]],
        );
        $items = $response->json('items');

        if ($items === null) {
            return collect();
        }

        if (! is_array($items)) {
            throw new GoogleCalendarResponseException('Google Calendar API応答に共有設定がありません。');
        }

        return collect($items)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): GoogleCalendarAclRule => $this->mapper->aclRule($item))
            ->values();
    }

    /**
     * 指定カレンダーへ共有権限を追加する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @param  string  $email  共有・招待するGoogleアカウントのメールアドレス
     * @param  string  $role  Google側へ設定する権限種別
     * @param  bool  $sendNotification  Googleから通知メールを送信する場合はtrue
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException カレンダーIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function createAclRule(
        User $user,
        string $calendarId,
        string $email,
        string $role,
        bool $sendNotification,
    ): void {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->resource->calendarUrl($calendarId).'/acl',
            [
                'query' => ['sendNotifications' => $sendNotification],
                'json' => [
                    'scope' => ['type' => 'user', 'value' => trim($email)],
                    'role' => $role,
                ],
            ],
        );
    }

    /**
     * 指定カレンダーの共有権限を削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @param  string  $ruleId  共有権限ルールID
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException カレンダーIDまたは共有ルールIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function deleteAclRule(User $user, string $calendarId, string $ruleId): void
    {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->resource->calendarUrl($calendarId).'/acl/'.rawurlencode($ruleId),
        );
    }
}
