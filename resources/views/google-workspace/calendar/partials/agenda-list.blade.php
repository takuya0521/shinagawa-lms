<div class="gw-calendar-agenda">
    @forelse ($events->groupBy(static fn ($event) => $event->startsAt->format('Y-m-d')) as $date => $dayEvents)
        @php($dateObject = \Carbon\CarbonImmutable::parse($date, config('app.timezone')))
        <section class="gw-agenda-day">
            <header>
                <span>{{ $dateObject->format('j') }}</span>
                <div>
                    <strong>{{ $dateObject->locale('ja')->isoFormat('dddd') }}</strong>
                    <small>{{ $dateObject->format('Y年n月') }}</small>
                </div>
            </header>
            <div class="gw-agenda-day__events">
                @foreach ($dayEvents as $event)
                    <article class="gw-agenda-event">
                        <time>{{ $event->isAllDay ? '終日' : $event->startsAt->format('H:i') }}</time>
                        <span class="gw-calendar-event__dot"></span>
                        <div>
                            <a
                                href="{{
                                    route('google-workspace.calendar.events.show', [$event->calendarId, $event->id])
                                }}"
                            >{{ $event->title }}</a>
                            <p>{{ $event->location ?? $event->scheduleLabel() }}</p>
                        </div>
                        <div class="gw-agenda-event__actions">
                            @if ($event->meetLink !== null)
                                <a
                                    href="{{ $event->meetLink }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Google Meetに参加"
                                ><x-google-icon name="meet" :size="19" /></a>
                            @endif
                            @if ($event->htmlLink !== null)
                                <a href="{{ $event->htmlLink }}" target="_blank" rel="noopener noreferrer">Googleで開く</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="gw-empty-state">
            <span class="gw-empty-state__icon"><x-google-icon name="calendar" :size="42" /></span>
            <h3>予定はありません</h3>
            <p>この期間に登録された予定はありません。</p>
        </div>
    @endforelse
</div>
