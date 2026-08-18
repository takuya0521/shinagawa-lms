<?php

namespace App\Services\GoogleCalendar\Support;

use App\Data\GoogleCalendarAclRule;
use App\Data\GoogleCalendarAttendee;
use App\Data\GoogleCalendarCalendar;
use App\Data\GoogleCalendarEvent;
use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Calendar API固有の配列を画面表示用DTOへ変換する。
 */
final class GoogleCalendarMapper
{
    /**
     * Google Calendar API応答をカレンダーDTOへ変換する。
     *
     * @param  array<array-key, mixed>  $calendar  Google Calendar APIのカレンダー応答
     * @return GoogleCalendarCalendar 画面表示に使用するカレンダー情報
     */
    public function calendar(array $calendar): GoogleCalendarCalendar
    {
        return new GoogleCalendarCalendar(
            id: $this->requiredString($calendar, 'id'),
            summary: $this->requiredString($calendar, 'summary'),
            description: $this->optionalString($calendar, 'description'),
            timeZone: $this->optionalString($calendar, 'timeZone'),
            accessRole: $this->optionalString($calendar, 'accessRole') ?? 'reader',
            primary: (bool) ($calendar['primary'] ?? false),
            selected: (bool) ($calendar['selected'] ?? false),
            backgroundColor: $this->optionalString($calendar, 'backgroundColor'),
        );
    }

    /**
     * Google Calendar API応答を予定DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $event  Google Calendar APIの予定応答
     * @param  string  $calendarId  Google CalendarのカレンダーID
     * @return GoogleCalendarEvent 画面表示に使用する予定情報
     */
    public function event(array $event, string $calendarId): GoogleCalendarEvent
    {
        $start = is_array($event['start'] ?? null) ? $event['start'] : [];
        $end = is_array($event['end'] ?? null) ? $event['end'] : [];
        $isAllDay = is_string($start['date'] ?? null);
        $startValue = $isAllDay ? $start['date'] ?? null : $start['dateTime'] ?? null;
        $endValue = $isAllDay ? $end['date'] ?? null : $end['dateTime'] ?? null;
        $attendees = is_array($event['attendees'] ?? null) ? $event['attendees'] : [];
        $recurrence = is_array($event['recurrence'] ?? null)
            ? array_values(array_filter($event['recurrence'], 'is_string'))
            : [];

        return new GoogleCalendarEvent(
            id: $this->requiredString($event, 'id'),
            calendarId: $calendarId,
            title: $this->optionalString($event, 'summary') ?? '無題の予定',
            description: $this->optionalString($event, 'description'),
            startsAt: $this->parseDate($startValue, $isAllDay),
            endsAt: $this->parseDate($endValue, $isAllDay),
            isAllDay: $isAllDay,
            location: $this->optionalString($event, 'location'),
            htmlLink: $this->optionalString($event, 'htmlLink'),
            meetLink: $this->meetLink($event),
            recurrence: $recurrence,
            attendees: collect($attendees)
                ->filter(static fn (mixed $attendee): bool => is_array($attendee))
                ->map(fn (array $attendee): GoogleCalendarAttendee => new GoogleCalendarAttendee(
                    email: $this->requiredString($attendee, 'email'),
                    displayName: $this->optionalString($attendee, 'displayName'),
                    responseStatus: $this->optionalString($attendee, 'responseStatus') ?? 'needsAction',
                    organizer: (bool) ($attendee['organizer'] ?? false),
                    self: (bool) ($attendee['self'] ?? false),
                ))
                ->values(),
            status: $this->optionalString($event, 'status') ?? 'confirmed',
            canModify: ! (bool) ($event['locked'] ?? false),
        );
    }

    /**
     * Google Calendar API応答を共有権限DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $rule  Google Calendar APIの共有ルール応答
     * @return GoogleCalendarAclRule 画面表示に使用する共有ルール情報
     */
    public function aclRule(array $rule): GoogleCalendarAclRule
    {
        return new GoogleCalendarAclRule(
            id: $this->requiredString($rule, 'id'),
            scopeType: $this->nestedString($rule, ['scope', 'type']) ?? 'default',
            scopeValue: $this->nestedString($rule, ['scope', 'value']),
            role: $this->requiredString($rule, 'role'),
        );
    }

    /**
     * 予定応答からGoogle Meet参加URLを取得する。
     *
     * @param  array<array-key, mixed>  $event  Google Calendar APIの予定応答
     * @return ?string Google Meet参加URL。会議情報がない場合はnull
     */
    private function meetLink(array $event): ?string
    {
        $hangoutLink = $this->optionalString($event, 'hangoutLink');

        if ($hangoutLink !== null) {
            return $hangoutLink;
        }

        $conferenceData = is_array($event['conferenceData'] ?? null) ? $event['conferenceData'] : [];
        $entryPoints = is_array($conferenceData['entryPoints'] ?? null) ? $conferenceData['entryPoints'] : [];

        foreach ($entryPoints as $entryPoint) {
            if (! is_array($entryPoint) || ($entryPoint['entryPointType'] ?? null) !== 'video') {
                continue;
            }

            $uri = $entryPoint['uri'] ?? null;

            if (is_string($uri) && $uri !== '') {
                return $uri;
            }
        }

        return null;
    }

    /**
     * Google APIの日付文字列をアプリケーション日時へ変換する。
     *
     * @param  mixed  $value  変換対象の値
     * @param  bool  $isAllDay  isAllDayの入力値
     * @return CarbonImmutable アプリケーションのタイムゾーンへ変換した日時
     *
     * @throws GoogleCalendarResponseException 日時が未設定または解析できない場合
     */
    private function parseDate(mixed $value, bool $isAllDay): CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            throw new GoogleCalendarResponseException('Google Calendar API応答に予定日時がありません。');
        }

        try {
            $date = CarbonImmutable::parse($value);

            return $isAllDay
                ? $date->startOfDay()
                : $date->setTimezone((string) config('app.timezone'));
        } catch (InvalidFormatException $exception) {
            throw new GoogleCalendarResponseException(
                'Google Calendar APIの予定日時を解析できません。',
                previous: $exception,
            );
        }
    }

    /**
     * API応答から必須文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  Google Calendar API応答
     * @param  string  $key  取得する必須項目名
     * @return string 指定キーに対応する必須文字列
     *
     * @throws GoogleCalendarResponseException 必須項目が存在しない場合
     */
    private function requiredString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new GoogleCalendarResponseException("Google Calendar API応答に{$key}がありません。");
        }

        return $value;
    }

    /**
     * API応答から任意文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  Google Calendar API応答
     * @param  string  $key  取得する任意項目名
     * @return ?string 指定キーに対応する文字列。値がない場合はnull
     */
    private function optionalString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * API応答の入れ子項目から任意文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  Google Calendar API応答
     * @param  list<string>  $keys  上位階層から順に並べた項目名
     * @return ?string 指定した階層にある文字列。値がない場合はnull
     */
    private function nestedString(array $values, array $keys): ?string
    {
        $current = $values;

        foreach ($keys as $index => $key) {
            $value = $current[$key] ?? null;

            if ($index === array_key_last($keys)) {
                return is_string($value) && $value !== '' ? $value : null;
            }

            if (! is_array($value)) {
                return null;
            }

            $current = $value;
        }

        return null;
    }
}
