<header class="gw-hero">
            <div class="gw-hero__title">
                <img src="{{ asset('images/google/calendar.svg') }}" alt="" width="48" height="48">
                <div><p>{{ $event->scheduleLabel() }}</p><h1>{{ $event->title }}</h1></div>
            </div>
            <a
                class="gw-button gw-button--secondary"
                href="{{ route('google-workspace.calendar.index', ['calendar_id' => $calendarId]) }}"
            >予定一覧へ戻る</a>
        </header>

        <div class="gw-grid gw-grid--two">
            <section class="gw-section">
                <div class="gw-section__header"><div><p>予定情報</p><h2>詳細</h2></div></div>
                <dl class="gw-definition">
                    <div><dt>日時</dt><dd>{{ $event->scheduleLabel() }}</dd></div>
                    <div><dt>場所</dt><dd>{{ $event->location ?? '—' }}</dd></div>
                    <div><dt>説明</dt><dd>{!! nl2br(e($event->description ?? '—')) !!}</dd></div>
                    <div>
                    <dt>繰り返し</dt>
                    <dd>{{ $event->isRecurring() ? implode(' / ', $event->recurrence) : 'なし' }}</dd>
                    </div>
                </dl>
                <div class="gw-actions">
                    @if ($event->meetLink !== null)
                        <a href="{{ $event->meetLink }}" target="_blank" rel="noopener noreferrer">Google Meetに参加</a>
                    @endif
                    @if ($event->htmlLink !== null)
                        <a
                            href="{{ $event->htmlLink }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Google Calendarで開く</a>
                    @endif
                </div>
                <h3 class="gw-subheading">参加者</h3>
                <div class="gw-compact-list">
                    @forelse ($event->attendees as $attendee)
                        <div>
                        <span>
                        <strong>{{ $attendee->displayName ?? $attendee->email }}</strong>
                        <small>{{ $attendee->email }} / {{ $attendee->responseLabel() }}</small>
                        </span>
                        </div>
                    @empty
                        <p class="gw-empty">参加者は登録されていません。</p>
                    @endforelse
                </div>
            </section>

            @if ($event->canModify)
                <section class="gw-section">
                    <div class="gw-section__header"><div><p>予定を変更</p><h2>編集</h2></div></div>
                    <form
                        class="gw-form"
                        method="POST"
                        action="{{ route('google-workspace.calendar.events.update', [$calendarId, $event->id]) }}"
                    >
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="calendar_id" value="{{ $calendarId }}">
                        <label>
                        <span>予定名</span>
                        <input type="text" name="title" value="{{ $event->title }}" required>
                        </label>
                        <label
                            class="gw-check"
                        >
                        <input type="checkbox" name="all_day" value="1" @checked($event->isAllDay)>
                        <span>終日予定</span>
                        </label>
                        <div class="gw-form__row">
                            <label>
                            <span>開始日時</span>
                            <input
                                type="datetime-local"
                                name="start_at"
                                value="{{ $event->isAllDay ? '' : $event->startsAt->format('Y-m-d\TH:i') }}"
                            >
                            </label>
                            <label>
                            <span>終了日時</span>
                            <input
                                type="datetime-local"
                                name="end_at"
                                value="{{ $event->isAllDay ? '' : $event->endsAt->format('Y-m-d\TH:i') }}"
                            >
                            </label>
                        </div>
                        <div class="gw-form__row">
                            <label>
                            <span>終日開始日</span>
                            <input
                                type="date"
                                name="start_date"
                                value="{{ $event->isAllDay ? $event->startsAt->format('Y-m-d') : '' }}"
                            >
                            </label>
                            <label>
                            <span>終日終了日</span>
                            <input
                                type="date"
                                name="end_date"
                                value="{{ $event->isAllDay ? $event->endsAt->subDay()->format('Y-m-d') : '' }}"
                            >
                            </label>
                        </div>
                        <label>
                        <span>説明</span>
                        <textarea name="description" rows="4">{{ $event->description }}</textarea>
                        </label>
                        <label><span>場所</span><input type="text" name="location" value="{{ $event->location }}"></label>
                        <label>
                        <span>参加者</span>
                        <textarea
                            name="attendees"
                            rows="3"
                        >{{ $event->attendees->pluck('email')->implode(', ') }}</textarea>
                        </label>
                        <div class="gw-form__row">
                            <label>
                            <span>繰り返し</span>
                            <select name="recurrence" required>
                            <option value="none" @selected($event->recurrenceType() === 'none')>なし</option>
                            <option value="daily" @selected($event->recurrenceType() === 'daily')>毎日</option>
                            <option value="weekly" @selected($event->recurrenceType() === 'weekly')>毎週</option>
                            <option value="monthly" @selected($event->recurrenceType() === 'monthly')>毎月</option>
                            <option value="yearly" @selected($event->recurrenceType() === 'yearly')>毎年</option>
                            </select>
                            </label>
                            <label><span>繰り返し終了日</span><input type="date" name="recurrence_until"></label>
                        </div>
                        <input type="hidden" name="time_zone" value="{{ config('app.timezone') }}">
                        <label>
                        <span>招待通知</span>
                        <select name="send_updates">
                        <option value="all">全参加者へ通知</option>
                        <option value="externalOnly">組織外のみ通知</option>
                        <option value="none">通知しない</option>
                        </select>
                        </label>
                        <label
                            class="gw-check"
                        >
                            <input type="checkbox" name="create_meet" value="1">
                            <span>新しいGoogle Meetリンクを発行する</span>
                        </label>
                        <button class="gw-button gw-button--primary" type="submit">予定を更新</button>
                    </form>
                    <form
                        class="gw-danger-zone"
                        method="POST"
                        action="{{ route('google-workspace.calendar.events.destroy', [$calendarId, $event->id]) }}"
                        data-confirm-message="この予定を削除しますか？"
                    >
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="send_updates" value="all">
                        <button class="gw-button gw-button--danger" type="submit">予定を削除</button>
                    </form>
                </section>
            @endif
        </div>
