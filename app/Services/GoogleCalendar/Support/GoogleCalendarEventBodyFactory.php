<?php

namespace App\Services\GoogleCalendar\Support;

use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * 検証済み入力をGoogle Calendar Eventリソースへ変換する。
 */
final class GoogleCalendarEventBodyFactory
{
    /**
     * @param  array<string, mixed>  $attributes  Form Requestで検証済みの予定入力
     * @return array<string, mixed> Google Calendarへ送る予定データ
     */
    public function make(array $attributes): array
    {
        $allDay = (bool) ($attributes['all_day'] ?? false);
        $body = [
            'summary' => trim((string) ($attributes['summary'] ?? '')),
            'description' => $attributes['description'] ?? null,
            'location' => $attributes['location'] ?? null,
            'visibility' => $attributes['visibility'] ?? 'default',
        ];

        if ($allDay) {
            $startDate = CarbonImmutable::parse((string) ($attributes['start_date'] ?? ''));
            $endDate = CarbonImmutable::parse((string) ($attributes['end_date'] ?? ''));
            $body['start'] = ['date' => $startDate->format('Y-m-d')];
            $body['end'] = ['date' => $endDate->addDay()->format('Y-m-d')];
        } else {
            $timeZone = (string) ($attributes['time_zone'] ?? config('app.timezone'));
            $start = CarbonImmutable::parse((string) ($attributes['start_at'] ?? ''), $timeZone);
            $end = CarbonImmutable::parse((string) ($attributes['end_at'] ?? ''), $timeZone);
            $body['start'] = ['dateTime' => $start->toIso8601String(), 'timeZone' => $timeZone];
            $body['end'] = ['dateTime' => $end->toIso8601String(), 'timeZone' => $timeZone];
        }

        $attendees = $attributes['attendees'] ?? [];
        $body['attendees'] = is_array($attendees)
            ? array_map(
                static fn (string $email): array => ['email' => $email],
                array_values(array_filter($attendees, 'is_string')),
            )
            : [];

        $reminders = $attributes['reminder_minutes'] ?? [];
        $body['reminders'] = is_array($reminders) && $reminders !== []
            ? [
                'useDefault' => false,
                'overrides' => array_map(
                    static fn (int $minutes): array => ['method' => 'popup', 'minutes' => $minutes],
                    array_values(array_map('intval', $reminders)),
                ),
            ]
            : ['useDefault' => true];

        $body['recurrence'] = $this->recurrence($attributes);

        if ((bool) ($attributes['create_meet'] ?? false)) {
            $body['conferenceData'] = [
                'createRequest' => [
                    'requestId' => Str::uuid()->toString(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ];
        }

        return $body;
    }

    /**
     * 検証済み入力からGoogle CalendarのRRULEを生成する。
     *
     * @param  array<string, mixed>  $attributes  Form Requestで検証済みの予定入力
     * @return list<string> Google Calendarへ送る繰り返しルール
     */
    private function recurrence(array $attributes): array
    {
        $recurrence = (string) ($attributes['recurrence'] ?? 'none');

        if ($recurrence === 'none') {
            return [];
        }

        $rule = match ($recurrence) {
            'daily' => 'RRULE:FREQ=DAILY',
            'weekly' => 'RRULE:FREQ=WEEKLY',
            'monthly' => 'RRULE:FREQ=MONTHLY',
            'yearly' => 'RRULE:FREQ=YEARLY',
            default => throw new GoogleCalendarResponseException('繰り返し設定が不正です。'),
        };
        $until = $attributes['recurrence_until'] ?? null;

        if (is_string($until) && $until !== '') {
            $rule .= ';UNTIL='.CarbonImmutable::parse($until)->endOfDay()->utc()->format('Ymd\THis\Z');
        }

        return [$rule];
    }
}
