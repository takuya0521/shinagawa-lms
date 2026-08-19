<?php

namespace Tests\Unit\Data;

use App\Data\GoogleMeetConference;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * GoogleMeetConferenceの会議時間表示を確認する。
 */
final class GoogleMeetConferenceTest extends TestCase
{
    /**
     * 1分未満の会議時間が小数の分数ではなく秒単位で表示されることを確認する。
     *
     * 前提: 約10秒で終了した会議履歴を準備する。
     * 処理: `durationLabel`を実行する。
     * 期待結果: 「10秒」が返る。
     */
    public function test_duration_label_formats_short_conference_in_seconds(): void
    {
        $conference = $this->conference(
            startedAt: '2026-08-11 03:49:00.000000',
            endedAt: '2026-08-11 03:49:09.824962',
        );

        self::assertSame('10秒', $conference->durationLabel());
    }

    /**
     * 1分以上の端数秒を含む会議時間が分と秒で表示されることを確認する。
     *
     * 前提: 1分30秒で終了した会議履歴を準備する。
     * 処理: `durationLabel`を実行する。
     * 期待結果: 「1分30秒」が返る。
     */
    public function test_duration_label_formats_minutes_and_seconds(): void
    {
        $conference = $this->conference(
            startedAt: '2026-08-11 03:49:00',
            endedAt: '2026-08-11 03:50:30',
        );

        self::assertSame('1分30秒', $conference->durationLabel());
    }

    /**
     * 開催中の会議履歴が経過時間ではなく開催中表示になることを確認する。
     *
     * 前提: 終了日時がない会議履歴を準備する。
     * 処理: `durationLabel`を実行する。
     * 期待結果: 「開催中」が返る。
     */
    public function test_duration_label_returns_active_label_without_end_time(): void
    {
        $conference = $this->conference(
            startedAt: '2026-08-11 03:49:00',
            endedAt: null,
        );

        self::assertSame('開催中', $conference->durationLabel());
    }

    /**
     * 会議時間表示テスト用のDTOを生成する。
     *
     * @param  string  $startedAt  開始日時
     * @param  string|null  $endedAt  終了日時
     * @return GoogleMeetConference テスト対象DTO
     */
    private function conference(string $startedAt, ?string $endedAt): GoogleMeetConference
    {
        return new GoogleMeetConference(
            name: 'conferenceRecords/conference001',
            spaceName: 'spaces/space001',
            startedAt: CarbonImmutable::parse($startedAt, 'Asia/Tokyo'),
            endedAt: $endedAt === null ? null : CarbonImmutable::parse($endedAt, 'Asia/Tokyo'),
            expiresAt: null,
        );
    }
}
