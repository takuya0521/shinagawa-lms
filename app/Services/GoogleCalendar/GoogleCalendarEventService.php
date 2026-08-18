<?php

namespace App\Services\GoogleCalendar;

use App\Data\GoogleCalendarEvent;
use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleCalendar\Support\GoogleCalendarConfiguration;
use App\Services\GoogleCalendar\Support\GoogleCalendarEventBodyFactory;
use App\Services\GoogleCalendar\Support\GoogleCalendarMapper;
use App\Services\GoogleCalendar\Support\GoogleCalendarResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/**
 * Google Calendar予定の取得・作成・更新・削除を担当する。
 */
final class GoogleCalendarEventService
{
    private const DETAIL_CACHE_SECONDS = 120;

    private const EVENT_FIELDS = 'items('
        .'id,summary,description,start,end,location,htmlLink,hangoutLink,'
        .'conferenceData,recurrence,attendees,status,locked'
        .'),nextPageToken';

    /**
     * 予定操作で認証・本文生成・応答変換の条件を統一するため、共通依存を注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleCalendarConfiguration  $configuration  Calendar APIのURI・スコープ・取得上限を提供する設定サービス
     * @param  GoogleCalendarResource  $resource  GoogleリソースIDとURLを安全に扱うサービス
     * @param  GoogleCalendarMapper  $mapper  Google API応答をDTOへ変換するサービス
     * @param  GoogleCalendarEventBodyFactory  $bodyFactory  検証済み予定入力をGoogle API形式へ変換するサービス
     * @param  GoogleWorkspaceCache  $cache  予定詳細をSWR表示するためのキャッシュ
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleCalendarConfiguration $configuration,
        private readonly GoogleCalendarResource $resource,
        private readonly GoogleCalendarMapper $mapper,
        private readonly GoogleCalendarEventBodyFactory $bodyFactory,
        private readonly GoogleWorkspaceCache $cache,
    ) {}

    /**
     * 指定条件に一致する予定一覧を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @param  ?CarbonImmutable  $timeMin  取得開始日時
     * @param  ?CarbonImmutable  $timeMax  取得終了日時
     * @param  ?string  $keyword  検索キーワード
     * @return Collection<int, GoogleCalendarEvent> 開始日時順の予定一覧
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException API応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function events(
        User $user,
        string $calendarId,
        ?CarbonImmutable $timeMin = null,
        ?CarbonImmutable $timeMax = null,
        ?string $keyword = null,
    ): Collection {
        $from = $timeMin ?? CarbonImmutable::now((string) config('app.timezone'))->startOfDay();
        $to = $timeMax ?? $from->addDays($this->configuration->daysAhead())->endOfDay();
        $query = [
            'fields' => self::EVENT_FIELDS,
            'maxResults' => $this->configuration->pageSize(),
            'orderBy' => 'startTime',
            'showDeleted' => false,
            'singleEvents' => true,
            'timeMax' => $to->utc()->toIso8601String(),
            'timeMin' => $from->utc()->toIso8601String(),
            'timeZone' => (string) config('app.timezone'),
        ];

        if ($keyword !== null && trim($keyword) !== '') {
            $query['q'] = trim($keyword);
        }

        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'GET',
            $this->resource->calendarUrl($calendarId).'/events',
            ['query' => $query],
        );
        $items = $response->json('items');

        if ($items === null) {
            return collect();
        }

        if (! is_array($items)) {
            throw new GoogleCalendarResponseException('Google Calendar API応答に予定一覧がありません。');
        }

        return collect($items)
            ->filter(static fn (mixed $event): bool => is_array($event))
            ->map(fn (array $event): GoogleCalendarEvent => $this->mapper->event($event, $calendarId))
            ->values();
    }

    /**
     * 指定カレンダーの予定情報を取得する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @param  string  $eventId  Google Calendarの予定ID
     * @return GoogleCalendarEvent 指定予定の画面表示用情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException API応答またはIDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function event(User $user, string $calendarId, string $eventId): GoogleCalendarEvent
    {
        $calendarUrl = $this->resource->calendarUrl($calendarId);
        $validatedEventId = $this->resource->eventId($eventId);

        return $this->cache->remember(
            $user,
            'calendar',
            'event:'.sha1($calendarId).':'.$validatedEventId,
            self::DETAIL_CACHE_SECONDS,
            function () use ($user, $calendarId, $calendarUrl, $validatedEventId): GoogleCalendarEvent {
                $response = $this->client->send(
                    $user,
                    $this->configuration->requiredScopes(),
                    'GET',
                    $calendarUrl.'/events/'.$validatedEventId,
                    ['query' => [
                        'fields' => 'id,summary,description,start,end,location,htmlLink,hangoutLink,'
                            .'conferenceData,recurrence,attendees,status,locked',
                    ]],
                );
                $event = $response->json();

                if (! is_array($event)) {
                    throw new GoogleCalendarResponseException('Google Calendar API応答に予定情報がありません。');
                }

                return $this->mapper->event($event, $calendarId);
            },
        );
    }

    /**
     * Google Calendarに新しい予定を作成する。
     *
     * @param  array<string, mixed>  $attributes  検証済みの予定名・日時・参加者・通知設定
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @return GoogleCalendarEvent Google APIが返した作成後の予定情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException 入力またはAPI応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function createEvent(User $user, string $calendarId, array $attributes): GoogleCalendarEvent
    {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->resource->calendarUrl($calendarId).'/events',
            [
                'query' => [
                    'conferenceDataVersion' => 1,
                    'sendUpdates' => (string) ($attributes['send_updates'] ?? 'all'),
                ],
                'json' => $this->bodyFactory->make($attributes),
            ],
        );
        $event = $response->json();

        if (! is_array($event)) {
            throw new GoogleCalendarResponseException('Google Calendar API応答に予定作成結果がありません。');
        }

        return $this->mapper->event($event, $calendarId);
    }

    /**
     * Google Calendarの予定を更新する。
     *
     * @param  array<string, mixed>  $attributes  検証済みの予定名・日時・参加者・通知設定
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @param  string  $eventId  Google Calendarの予定ID
     * @return GoogleCalendarEvent Google APIが返した更新後の予定情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException 入力・ID・API応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function updateEvent(
        User $user,
        string $calendarId,
        string $eventId,
        array $attributes,
    ): GoogleCalendarEvent {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'PATCH',
            $this->resource->calendarUrl($calendarId).'/events/'.$this->resource->eventId($eventId),
            [
                'query' => [
                    'conferenceDataVersion' => 1,
                    'sendUpdates' => (string) ($attributes['send_updates'] ?? 'all'),
                ],
                'json' => $this->bodyFactory->make($attributes),
            ],
        );
        $event = $response->json();

        if (! is_array($event)) {
            throw new GoogleCalendarResponseException('Google Calendar API応答に更新結果がありません。');
        }

        return $this->mapper->event($event, $calendarId);
    }

    /**
     * Google Calendarの予定を削除する。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @param  string  $eventId  Google Calendarの予定ID
     * @param  string  $sendUpdates  参加者へ通知する範囲
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException カレンダーIDまたは予定IDが不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function deleteEvent(
        User $user,
        string $calendarId,
        string $eventId,
        string $sendUpdates = 'all',
    ): void {
        $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'DELETE',
            $this->resource->calendarUrl($calendarId).'/events/'.$this->resource->eventId($eventId),
            ['query' => ['sendUpdates' => $sendUpdates]],
        );
    }
}
