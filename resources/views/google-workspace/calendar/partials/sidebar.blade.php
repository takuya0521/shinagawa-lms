<aside class="gw-calendar-sidebar" aria-label="Google Calendarナビゲーション">
    <details class="gw-new-menu" data-disclosure>
        <summary class="gw-new-button">
            <x-google-icon name="plus" :size="24" />
            <span>作成</span>
            <x-google-icon name="chevron-down" :size="17" />
        </summary>
        <div class="gw-new-menu__panel gw-calendar-create-panel">
            @include('google-workspace.calendar.partials.create-event')
            @include('google-workspace.calendar.partials.create-calendar')
        </div>
    </details>

    <section class="gw-mini-calendar" aria-label="{{ $anchor->format('Y年n月') }}のミニカレンダー">
        <header>
            <strong>{{ $anchor->format('Y年n月') }}</strong>
            <x-google-icon name="calendar" :size="18" />
        </header>
        <div class="gw-mini-calendar__weekdays" aria-hidden="true">
            @foreach (['日', '月', '火', '水', '木', '金', '土'] as $weekday)
                <span>{{ $weekday }}</span>
            @endforeach
        </div>
        <div class="gw-mini-calendar__days">
            @foreach ($calendarDays as $day)
                <a
                    href="{{ route('google-workspace.calendar.index', [
                        'calendar_id' => $calendarId,
                        'display' => 'agenda',
                        'date_from' => $day->format('Y-m-d'),
                        'date_to' => $day->format('Y-m-d'),
                        'anchor' => $day->format('Y-m-d'),
                    ]) }}"
                    @class([
                        'is-outside' => $day->month !== $anchor->month,
                        'is-today' => $day->isToday(),
                    ])
                >{{ $day->day }}</a>
            @endforeach
        </div>
    </section>

    <section class="gw-calendar-list">
        <h3>マイカレンダー</h3>
        <nav>
            @foreach ($calendars as $calendar)
                <a
                    href="{{ route('google-workspace.calendar.index', [
                        'calendar_id' => $calendar->id,
                        'display' => $displayMode,
                        'anchor' => $anchor->format('Y-m-d'),
                    ]) }}"
                    @class(['is-active' => $calendar->id === $calendarId])
                    @if ($calendar->id === $calendarId) aria-current="page" @endif
                >
                    <span class="gw-calendar-color gw-calendar-color--{{ ($loop->index % 8) + 1 }}"></span>
                    <span>{{ $calendar->summary }}</span>
                </a>
            @endforeach
        </nav>
    </section>

    <div class="gw-calendar-sidebar__tools">
        <a
            href="{{ route('google-workspace.calendar.index', [
                'calendar_id' => $calendarId,
                'display' => $displayMode,
                'anchor' => $anchor->format('Y-m-d'),
                'manage' => 1,
            ]) }}"
            @class(['is-active' => $showsManagement])
        >
            <x-google-icon name="settings" :size="18" />
            <span>設定と共有</span>
        </a>
    </div>

    @if ($showsManagement)
        <div class="gw-calendar-management" data-deferred-section>
            @include('google-workspace.calendar.partials.calendar-management')
            @include('google-workspace.calendar.partials.free-busy')
        </div>
    @endif
</aside>
