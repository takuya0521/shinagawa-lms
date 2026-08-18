<div class="gw-calendar-month" role="grid" aria-label="{{ $calendarTitle }}">
    <div class="gw-calendar-month__weekdays" role="row">
        @foreach (['日', '月', '火', '水', '木', '金', '土'] as $weekday)
            <span role="columnheader">{{ $weekday }}</span>
        @endforeach
    </div>
    <div class="gw-calendar-month__days">
        @foreach ($calendarDays as $day)
            @php($dayEvents = $eventsByDate->get($day->format('Y-m-d'), collect()))
            <article
                @class([
                    'gw-calendar-day',
                    'is-outside' => $day->month !== $anchor->month,
                    'is-today' => $day->isToday(),
                ])
                role="gridcell"
                aria-label="{{ $day->format('Y年n月j日') }}"
            >
                <header>
                    <a
                        href="{{
                            route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                            'agenda', 'date_from' => $day->format('Y-m-d'), 'date_to' => $day->format('Y-m-d'),
                            'anchor' => $day->format('Y-m-d')])
                        }}"
                    >{{ $day->day }}</a>
                </header>
                <div class="gw-calendar-day__events">
                    @foreach ($dayEvents->take(4) as $event)
                        <a
                            class="gw-calendar-event"
                            href="{{
                                route('google-workspace.calendar.events.show', [$event->calendarId, $event->id])
                            }}"
                            title="{{ $event->title }} — {{ $event->scheduleLabel() }}"
                        >
                            <span class="gw-calendar-event__dot"></span>
                            <time>{{ $event->isAllDay ? '終日' : $event->startsAt->format('H:i') }}</time>
                            <strong>{{ $event->title }}</strong>
                            @if ($event->meetLink !== null)
                                <x-google-icon name="meet" :size="13" />
                                <span class="sr-only">Meetに参加</span>
                            @endif
                        </a>
                    @endforeach
                    @if ($dayEvents->count() > 4)
                        <a
                            class="gw-calendar-more"
                            href="{{
                                route('google-workspace.calendar.index', ['calendar_id' => $calendarId, 'display' =>
                                'agenda', 'date_from' => $day->format('Y-m-d'), 'date_to' => $day->format('Y-m-d'),
                                'anchor' => $day->format('Y-m-d')])
                            }}"
                        >他 {{ $dayEvents->count() - 4 }} 件</a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</div>
