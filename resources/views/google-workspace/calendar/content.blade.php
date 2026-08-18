@php
    $selectedCalendar = $calendars->first(static fn ($calendar) => $calendar->id === $calendarId);
    $calendarTitle = $anchor->locale('ja')->isoFormat('YYYY年M月');
@endphp

@if ($calendarError !== null)
    <section class="gw-alert gw-alert--error" role="alert">{{ $calendarError }}</section>
@endif

<div class="gw-calendar-shell">
    @include('google-workspace.calendar.partials.sidebar')

    <section class="gw-calendar-main" aria-labelledby="calendar-view-heading">
        <header class="gw-calendar-toolbar">
            <div class="gw-calendar-toolbar__navigation">
                <a
                    class="gw-button gw-button--secondary gw-button--compact"
                    href="{{
                        route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                        $displayMode, 'anchor' => now(config('app.timezone'))->format('Y-m-d')])
                    }}"
                >今日</a>
                <a
                    class="gw-icon-button"
                    href="{{
                        route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                        $displayMode, 'anchor' => $previousAnchor])
                    }}"
                    aria-label="前の期間"
                ><x-google-icon name="chevron-left" :size="20" /></a>
                <a
                    class="gw-icon-button"
                    href="{{
                        route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                        $displayMode, 'anchor' => $nextAnchor])
                    }}"
                    aria-label="次の期間"
                ><x-google-icon name="chevron-right" :size="20" /></a>
                <h2 id="calendar-view-heading">{{ $calendarTitle }}</h2>
            </div>

            <div class="gw-calendar-toolbar__actions">
                <a
                    class="gw-icon-button"
                    href="{{
                        route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                        $displayMode, 'anchor' => $anchor->format('Y-m-d'), 'refresh' => 1])
                    }}"
                    aria-label="Google Calendarを更新"
                    title="更新"
                ><x-google-icon name="refresh" :size="20" /></a>
                <nav class="gw-segmented" aria-label="カレンダー表示形式">
                    <a
                        href="{{
                            route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                            'month', 'anchor' => $anchor->format('Y-m-d')])
                        }}"
                        @class(['is-active' => $displayMode === 'month'])
                    >月</a>
                    <a
                        href="{{
                            route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                            'agenda', 'anchor' => $anchor->format('Y-m-d')])
                        }}"
                        @class(['is-active' => $displayMode === 'agenda'])
                    >予定</a>
                </nav>
            </div>
        </header>

        @if ($displayMode === 'month')
            @include('google-workspace.calendar.partials.month-grid')
        @else
            @include('google-workspace.calendar.partials.agenda-list')
        @endif
    </section>
</div>
