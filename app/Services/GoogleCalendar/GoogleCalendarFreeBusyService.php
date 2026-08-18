<?php

namespace App\Services\GoogleCalendar;

use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleCalendar\Support\GoogleCalendarConfiguration;
use App\Services\GoogleCalendar\Support\GoogleCalendarResource;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * 複数カレンダーの空き時間照会を担当する。
 */
final class GoogleCalendarFreeBusyService
{
    /**
     * 空き時間照会でも他のCalendar操作と同じ認証・設定条件を使うため、共通依存を注入する。
     *
     * @param  GoogleApiClient  $client  認証・再試行を共通化したGoogle APIクライアント
     * @param  GoogleCalendarConfiguration  $configuration  Calendar APIのURI・スコープ・取得上限を提供する設定サービス
     * @param  GoogleCalendarResource  $resource  GoogleリソースIDとURLを安全に扱うサービス
     */
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleCalendarConfiguration $configuration,
        private readonly GoogleCalendarResource $resource,
    ) {}

    /**
     * @param  list<string>  $calendarIds  照会対象カレンダーID
     * @param  User  $user  連携済みGoogleアカウントを識別するLMS利用者
     * @param  CarbonImmutable  $timeMin  取得開始日時
     * @param  CarbonImmutable  $timeMax  取得終了日時
     * @return array<string, list<array{start: string, end: string}>> カレンダー別予定あり時間帯
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google Calendar APIがエラーを返した場合
     * @throws GoogleCalendarResponseException 入力またはAPI応答が不正な場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function freeBusy(
        User $user,
        array $calendarIds,
        CarbonImmutable $timeMin,
        CarbonImmutable $timeMax,
    ): array {
        $response = $this->client->send(
            $user,
            $this->configuration->requiredScopes(),
            'POST',
            $this->resource->apiUrl('/freeBusy'),
            ['json' => [
                'timeMin' => $timeMin->utc()->toIso8601String(),
                'timeMax' => $timeMax->utc()->toIso8601String(),
                'timeZone' => (string) config('app.timezone'),
                'items' => array_map(
                    static fn (string $id): array => ['id' => $id],
                    $calendarIds,
                ),
            ]],
        );
        $calendars = $response->json('calendars');

        if (! is_array($calendars)) {
            throw new GoogleCalendarResponseException('Google Calendar API応答に空き時間情報がありません。');
        }

        $result = [];

        foreach ($calendars as $calendarId => $calendar) {
            if (! is_string($calendarId) || ! is_array($calendar)) {
                continue;
            }

            $busy = is_array($calendar['busy'] ?? null) ? $calendar['busy'] : [];
            $result[$calendarId] = array_values(array_filter(
                $busy,
                static fn (mixed $range): bool => is_array($range)
                    && is_string($range['start'] ?? null)
                    && is_string($range['end'] ?? null),
            ));
        }

        return $result;
    }
}
