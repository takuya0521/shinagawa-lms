<?php

namespace App\Services\GoogleCalendar\Support;

use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;

/**
 * Calendar APIのURL生成と予定ID検証を担当する。
 */
final class GoogleCalendarResource
{
    /**
     * URL生成とID検証を各Serviceへ重複させないため、Calendar設定Serviceを注入する。
     *
     * @param  GoogleCalendarConfiguration  $configuration  Calendar APIのURI・スコープ・取得上限を提供する設定サービス
     */
    public function __construct(
        private readonly GoogleCalendarConfiguration $configuration,
    ) {}

    /**
     * Calendar APIの基底URIへ指定パスを連結する。
     *
     * @param  string  $path  Calendar API基底URIへ追加する相対パス
     * @return string Calendar API基底URIとパスを結合したURL
     */
    public function apiUrl(string $path): string
    {
        return rtrim($this->configuration->apiUri(), '/').'/'.ltrim($path, '/');
    }

    /**
     * 指定カレンダーを操作するAPI URLを組み立てる。
     *
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @return string 指定カレンダーを操作するCalendar API URL
     *
     * @throws GoogleCalendarResponseException カレンダーIDが空の場合
     */
    public function calendarUrl(string $calendarId): string
    {
        if (trim($calendarId) === '') {
            throw new GoogleCalendarResponseException('Google CalendarのカレンダーIDがありません。');
        }

        return $this->apiUrl('/calendars/'.rawurlencode($calendarId));
    }

    /**
     * 外部入力された予定IDをURL利用向けに整形する。
     *
     * @param  string  $eventId  Google Calendarの予定ID
     * @return string 形式検証済みの予定ID
     *
     * @throws GoogleCalendarResponseException 予定IDの形式が不正な場合
     */
    public function eventId(string $eventId): string
    {
        if (preg_match('/\A[A-Za-z0-9_-]+\z/u', $eventId) !== 1) {
            throw new GoogleCalendarResponseException('Google Calendarの予定IDが不正です。');
        }

        return $eventId;
    }
}
