<?php

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Google Calendar一覧画面で同時に使用するカレンダーと予定をまとめる不変データ。
 */
final readonly class GoogleCalendarOverview
{
    /**
     * @param  Collection<int, GoogleCalendarCalendar>  $calendars  利用者が参照できるカレンダー一覧
     * @param  Collection<int, GoogleCalendarEvent>  $events  指定期間の予定一覧
     */
    public function __construct(
        public Collection $calendars,
        public Collection $events,
    ) {}
}
