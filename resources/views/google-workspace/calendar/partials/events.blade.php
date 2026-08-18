<section class="gw-section" aria-labelledby="calendar-events-heading">
    <div class="gw-section__header">
        <div>
            <p>{{ $selectedCalendar?->summary ?? '選択カレンダー' }}</p>
            <h2 id="calendar-events-heading">予定一覧</h2>
        </div>
        <span class="gw-count">{{ $events->count() }}件</span>
    </div>
    <div class="gw-card-list">
        @forelse ($events as $event)
            <article class="gw-item-card">
                <div class="gw-item-card__body">
                    <div class="gw-badges">
                        @if ($event->isAllDay)<span>終日</span>@endif
                        @if ($event->isRecurring())<span>繰り返し</span>@endif
                        @if ($event->meetLink !== null)<span>Meetあり</span>@endif
                    </div>
                    <h3>{{ $event->title }}</h3>
                    <p class="gw-meta">{{ $event->scheduleLabel() }}</p>
                    @if ($event->location !== null)<p>{{ $event->location }}</p>@endif
                </div>
                <div class="gw-actions">
                    <a
                        href="{{ route('google-workspace.calendar.events.show', [$event->calendarId, $event->id]) }}"
                    >詳細・編集</a>
                    @if ($event->meetLink !== null)
                        <a href="{{ $event->meetLink }}" target="_blank" rel="noopener noreferrer">Meetに参加</a>
                    @endif
                    @if ($event->htmlLink !== null)
                        <a href="{{ $event->htmlLink }}" target="_blank" rel="noopener noreferrer">Googleで開く</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="gw-empty">表示条件に一致する予定はありません。</p>
        @endforelse
    </div>
</section>
