<?php

namespace App\Services\GoogleCalendar;

use App\Data\GoogleCalendarCalendar;
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
 * Google Calendarのカレンダー一覧・作成・更新・削除を担当する。
 */
final class GoogleCalendarCatalogService
{
    /**
     * カレンダー操作で認証・URL検証・応答変換の条件を統一するため、共通依存を注入する。
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
     * Google API連携に必要な設定が揃っているか判定する。
     *
     * @return bool Google API利用に必要な設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->configuration->isConfigured();
    }

    /**
     * Calendar APIへ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Calendar APIへ要求するOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return $this->configuration->requiredScopes();
    }

    /**
     * 利用者が参照できるカレンダー一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @return Collection<int, GoogleCalendarCalendar> 利用者が参照できるカレンダー一覧
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException API応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function calendars(User $user): Collection
    {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->resource->apiUrl('/users/me/calendarList'),
            ['query' => [
                'fields' => 'items(id,summary,description,timeZone,accessRole,primary,selected,backgroundColor)',
                'maxResults' => 250,
                'showDeleted' => false,
                'showHidden' => true,
            ]],
        );
        $items = $response->json('items');

        if ($items === null) {
            return collect();
        }

        if (! is_array($items)) {
            throw new GoogleCalendarResponseException('Google Calendar API応答にカレンダー一覧がありません。');
        }

        return collect($items)
            ->filter(static fn (mixed $calendar): bool => is_array($calendar))
            ->map(fn (array $calendar): GoogleCalendarCalendar => $this->mapper->calendar($calendar))
            ->sortByDesc(static fn (GoogleCalendarCalendar $calendar): bool => $calendar->primary)
            ->values();
    }

    /**
     * Google Calendarに新しいカレンダーを作成する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $summary  カレンダーの表示名
     * @param  ?string  $description  説明文
     * @param  string  $timeZone  IANA形式のタイムゾーンID
     * @return GoogleCalendarCalendar Google APIが返した作成後のカレンダー情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException API応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function createCalendar(
        User $user,
        string $summary,
        ?string $description,
        string $timeZone,
    ): GoogleCalendarCalendar {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'POST',
            $this->resource->apiUrl('/calendars'),
            ['json' => [
                'summary' => trim($summary),
                'description' => $description,
                'timeZone' => $timeZone,
            ]],
        );
        $calendar = $response->json();

        if (! is_array($calendar)) {
            throw new GoogleCalendarResponseException('Google Calendar API応答に作成結果がありません。');
        }

        $calendar['accessRole'] = 'owner';
        $calendar['selected'] = true;

        return $this->mapper->calendar($calendar);
    }

    /**
     * Google Calendarのカレンダー情報を更新する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @param  string  $summary  カレンダーの表示名
     * @param  ?string  $description  説明文
     * @param  string  $timeZone  IANA形式のタイムゾーンID
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException カレンダーIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function updateCalendar(
        User $user,
        string $calendarId,
        string $summary,
        ?string $description,
        string $timeZone,
    ): void {
        $this->client->send(
            $user,
            $this->requiredScopes(),
            'PUT',
            $this->resource->calendarUrl($calendarId),
            ['json' => [
                'summary' => trim($summary),
                'description' => $description,
                'timeZone' => $timeZone,
            ]],
        );
    }

    /**
     * Google Calendarのカレンダーを削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException メインカレンダーまたはカレンダーIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function deleteCalendar(User $user, string $calendarId): void
    {
        if ($calendarId === 'primary') {
            throw new GoogleCalendarResponseException('メインカレンダーは削除できません。');
        }

        $this->client->send(
            $user,
            $this->requiredScopes(),
            'DELETE',
            $this->resource->calendarUrl($calendarId),
        );
    }
}
