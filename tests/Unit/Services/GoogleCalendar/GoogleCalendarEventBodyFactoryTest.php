<?php

namespace Tests\Unit\Services\GoogleCalendar;

use App\Services\GoogleCalendar\Support\GoogleCalendarEventBodyFactory;
use PHPUnit\Framework\TestCase;

/**
 * 検証済み予定入力がGoogle Calendar形式へ正しく変換されることを確認する。
 */
final class GoogleCalendarEventBodyFactoryTest extends TestCase
{
    /**
     * 終日予定の終了日がGoogle Calendarの排他的終了日へ補正されることを確認する。
     *
     * 前提: 8月10日から8月11日までの終日予定入力を準備する。
     * 処理: `make`を実行する。
     * 期待結果: API送信値の終了日が翌日の8月12日になる。
     */
    public function test_make_converts_all_day_end_date_to_exclusive_date(): void
    {
        $body = (new GoogleCalendarEventBodyFactory)->make([
            'summary' => '夏期講習',
            'all_day' => true,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'recurrence' => 'none',
        ]);

        self::assertSame(['date' => '2026-08-10'], $body['start']);
        self::assertSame(['date' => '2026-08-12'], $body['end']);
    }

    /**
     * 繰り返し終了日を含む週次予定がCalendar APIのRRULEへ変換されることを確認する。
     *
     * 前提: 週次繰り返しと終了日を指定した時刻予定を準備する。
     * 処理: `make`を実行する。
     * 期待結果: WEEKLYとUTC終了日時を含むRRULEが1件生成される。
     */
    public function test_make_builds_weekly_recurrence_rule_with_until_date(): void
    {
        $body = (new GoogleCalendarEventBodyFactory)->make([
            'summary' => '英語授業',
            'all_day' => false,
            'start_at' => '2026-08-10 10:00:00',
            'end_at' => '2026-08-10 11:00:00',
            'time_zone' => 'Asia/Tokyo',
            'recurrence' => 'weekly',
            'recurrence_until' => '2026-09-30',
        ]);

        self::assertCount(1, $body['recurrence']);
        self::assertStringStartsWith('RRULE:FREQ=WEEKLY;UNTIL=', $body['recurrence'][0]);
        self::assertStringEndsWith('Z', $body['recurrence'][0]);
    }
}
