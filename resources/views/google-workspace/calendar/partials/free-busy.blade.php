<details class="gw-panel">
    <summary>空き時間を確認</summary>
    <form class="gw-form" method="POST" action="{{ route('google-workspace.calendar.free-busy') }}">
        @csrf
        <fieldset class="gw-fieldset">
            <legend>対象カレンダー</legend>
            @foreach ($calendars as $calendar)
                <label
                    class="gw-check"
                >
                <input
                    type="checkbox"
                    name="calendar_ids[]"
                    value="{{ $calendar->id }}"
                    @checked($calendar->id === $calendarId)
                >
                <span>{{ $calendar->summary }}</span>
                </label>
            @endforeach
        </fieldset>
        <label><span>確認開始</span><input type="datetime-local" name="time_min" required></label>
        <label><span>確認終了</span><input type="datetime-local" name="time_max" required></label>
        <button class="gw-button gw-button--secondary" type="submit">予定あり時間帯を取得</button>
    </form>
    @if ($freeBusy !== [])
        <div class="gw-compact-list">
            @foreach ($freeBusy as $busyCalendarId => $ranges)
                <div class="gw-free-busy">
                    <strong>{{ $calendars->firstWhere('id', $busyCalendarId)?->summary ?? $busyCalendarId }}</strong>
                    @forelse ($ranges as $range)
                        <small>{{
                            \Carbon\CarbonImmutable::parse($range['start'])
                                ->setTimezone(config('app.timezone'))
                                ->format('Y/m/d H:i')
                        }}〜{{
                            \Carbon\CarbonImmutable::parse($range['end'])
                                ->setTimezone(config('app.timezone'))
                                ->format('Y/m/d H:i')
                        }}</small>
                    @empty
                        <small>予定ありの時間帯はありません。</small>
                    @endforelse
                </div>
            @endforeach
        </div>
    @endif
</details>
