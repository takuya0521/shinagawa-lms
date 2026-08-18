<?php

namespace App\Services\GoogleCalendar;

use App\Data\GoogleCalendarCalendar;
use App\Data\GoogleCalendarEvent;
use App\Data\GoogleCalendarOverview;
use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;
use App\Models\User;
use App\Services\GoogleCalendar\Support\GoogleCalendarConfiguration;
use App\Services\GoogleCalendar\Support\GoogleCalendarMapper;
use App\Services\GoogleCalendar\Support\GoogleCalendarResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * Google Calendar一覧画面のカレンダーと予定を取得する。
 */
final class GoogleCalendarOverviewService
{
    private const CALENDAR_CACHE_SECONDS = 300;

    private const EVENT_CACHE_SECONDS = 120;

    private const EVENT_FIELDS = 'items('
        .'id,summary,start,end,location,htmlLink,hangoutLink,'
        .'conferenceData(entryPoints(entryPointType,uri)),recurrence'
        .'),nextPageToken';

    /**
     * Calendar APIの選択実行とキャッシュ分離に必要な依存を受け取る。
     *
     * @param  GoogleApiClient  $client  認証・再試行・並列通信を共通化したGoogle APIクライアント
     * @param  GoogleCalendarConfiguration  $configuration  Calendar API設定
     * @param  GoogleCalendarResource  $resource  Calendar URL生成とID検証
     * @param  GoogleCalendarMapper  $mapper  Calendar API応答のDTO変換
     * @param  GoogleWorkspaceCache  $cache  利用者単位のSWRキャッシュ
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleCalendarConfiguration $configuration,
        private readonly GoogleCalendarResource $resource,
        private readonly GoogleCalendarMapper $mapper,
        private readonly GoogleWorkspaceCache $cache,
    ) {}

    /**
     * 利用可能なカレンダー一覧と指定期間の予定を取得する。
     * カレンダー一覧は表示月を変えても同じため長めに再利用し、予定だけを期間単位で更新する。
     * 両方が未取得の場合は同時送信して初回表示の待ち時間を抑える。
     *
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  string  $calendarId  表示対象カレンダーID
     * @param  CarbonImmutable  $from  予定取得期間の開始日時
     * @param  CarbonImmutable  $to  予定取得期間の終了日時
     * @param  string|null  $keyword  予定名・説明・場所の検索語
     * @return GoogleCalendarOverview Calendar一覧画面で使用するカレンダーと予定
     */
    public function overview(
        User $user,
        string $calendarId,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?string $keyword,
    ): GoogleCalendarOverview {
        $calendarCacheKey = 'calendar-list';
        $eventCacheKey = 'events:'.json_encode([
            'calendar_id' => $calendarId,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'keyword' => $keyword,
        ], JSON_THROW_ON_ERROR);
        $calendars = $this->cache->get($user, 'calendar', $calendarCacheKey);
        $events = $this->cache->get($user, 'calendar', $eventCacheKey);
        $requests = [];

        if (! $calendars instanceof Collection) {
            $requests['calendars'] = [
                'method' => 'GET',
                'url' => $this->resource->apiUrl('/users/me/calendarList'),
                'options' => ['query' => [
                    'fields' => 'items(id,summary,description,timeZone,accessRole,primary,selected,backgroundColor)',
                    'maxResults' => 250,
                    'showDeleted' => false,
                    'showHidden' => true,
                ]],
            ];
        }

        if (! $events instanceof Collection) {
            $eventQuery = [
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
                $eventQuery['q'] = trim($keyword);
            }

            $requests['events'] = [
                'method' => 'GET',
                'url' => $this->resource->calendarUrl($calendarId).'/events',
                'options' => ['query' => $eventQuery],
            ];
        }

        if ($requests !== []) {
            $responses = $this->client->sendMany(
                $user,
                $this->configuration->requiredScopes(),
                $requests,
            );

            if (isset($responses['calendars'])) {
                $calendars = $this->calendars($responses['calendars']);
                $this->cache->put(
                    $user,
                    'calendar',
                    $calendarCacheKey,
                    self::CALENDAR_CACHE_SECONDS,
                    $calendars,
                );
            }

            if (isset($responses['events'])) {
                $events = $this->events($responses['events'], $calendarId);
                $this->cache->put(
                    $user,
                    'calendar',
                    $eventCacheKey,
                    self::EVENT_CACHE_SECONDS,
                    $events,
                );
            }
        }

        return new GoogleCalendarOverview(
            calendars: $calendars instanceof Collection ? $calendars : collect(),
            events: $events instanceof Collection ? $events : collect(),
        );
    }

    /**
     * calendarList応答をカレンダー一覧へ変換する。
     *
     * @param  Response  $response  Google Calendar calendarList応答
     * @return Collection<int, GoogleCalendarCalendar> 利用者が参照できるカレンダー一覧
     */
    private function calendars(Response $response): Collection
    {
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
     * events.list応答を予定一覧へ変換する。
     *
     * @param  Response  $response  Google Calendar events.list応答
     * @param  string  $calendarId  予定が所属するカレンダーID
     * @return Collection<int, GoogleCalendarEvent> 指定期間の予定一覧
     */
    private function events(Response $response, string $calendarId): Collection
    {
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
}
