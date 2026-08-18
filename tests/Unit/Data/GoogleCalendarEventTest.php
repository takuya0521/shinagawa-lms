<?php

namespace Tests\Unit\Data;

use App\Data\GoogleCalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * GoogleCalendarEventの日時表示変換を確認する。
 */
final class GoogleCalendarEventTest extends TestCase
{
    /**
     * 同日内の時刻指定予定が開始時刻と終了時刻を重複なく表示することを確認する。
     *
     * 前提: 同日の10時から11時までの予定を準備する。
     * 処理: `scheduleLabel`を実行する。
     * 期待結果: 日付を一度だけ含む日本語日時が返る。
     */
    public function test_schedule_label_formats_same_day_timed_event(): void
    {
        $event = new GoogleCalendarEvent(
            id: 'event-001',
            calendarId: 'primary',
            title: '授業',
            description: null,
            startsAt: CarbonImmutable::parse('2026-08-10 10:00:00', 'Asia/Tokyo'),
            endsAt: CarbonImmutable::parse('2026-08-10 11:00:00', 'Asia/Tokyo'),
            isAllDay: false,
            location: null,
            htmlLink: null,
            meetLink: null,
            recurrence: [],
            attendees: new Collection,
            status: 'confirmed',
            canModify: true,
        );

        self::assertSame('2026年8月10日 10:00〜11:00', $event->scheduleLabel());
    }

    /**
     * 終日予定の排他的終了日が利用者向けの最終日に補正されることを確認する。
     *
     * 前提: API上で8月10日開始、8月12日終了の終日予定を準備する。
     * 処理: `scheduleLabel`を実行する。
     * 期待結果: 実際の対象期間である8月10日から8月11日までが表示される。
     */
    public function test_schedule_label_formats_all_day_event_with_inclusive_end(): void
    {
        $event = new GoogleCalendarEvent(
            id: 'event-002',
            calendarId: 'primary',
            title: '集中講座',
            description: null,
            startsAt: CarbonImmutable::parse('2026-08-10', 'Asia/Tokyo'),
            endsAt: CarbonImmutable::parse('2026-08-12', 'Asia/Tokyo'),
            isAllDay: true,
            location: null,
            htmlLink: null,
            meetLink: null,
            recurrence: [],
            attendees: new Collection,
            status: 'confirmed',
            canModify: true,
        );

        self::assertSame(
            '2026年8月10日〜2026年8月11日（終日）',
            $event->scheduleLabel(),
        );
    }
}
