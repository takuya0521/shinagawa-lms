<details class="gw-new-menu__section">
    <summary>
        <x-google-icon name="calendar" :size="20" />
        <span>予定</span>
    </summary>
    <div class="gw-calendar-create-form">
        <form class="gw-form" method="POST" action="{{ route('google-workspace.calendar.events.store') }}">
            @csrf
            <input type="hidden" name="calendar_id" value="{{ $calendarId }}">
            <label>
                <span>予定名</span>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="255">
            </label>
            <div class="gw-form__row">
                <label>
                    <span>開始日時</span>
                    <input type="datetime-local" name="start_at" value="{{ old('start_at') }}">
                </label>
                <label>
                    <span>終了日時</span>
                    <input type="datetime-local" name="end_at" value="{{ old('end_at') }}">
                </label>
            </div>
            <label class="gw-check">
                <input type="checkbox" name="all_day" value="1" @checked(old('all_day'))>
                <span>終日予定として作成する</span>
            </label>
            <div class="gw-form__row">
                <label>
                    <span>終日開始日</span>
                    <input type="date" name="start_date" value="{{ old('start_date') }}">
                </label>
                <label>
                    <span>終日終了日</span>
                    <input type="date" name="end_date" value="{{ old('end_date') }}">
                </label>
            </div>
            <label>
                <span>説明</span>
                <textarea name="description" rows="3" maxlength="10000">{{ old('description') }}</textarea>
            </label>
            <label>
                <span>場所</span>
                <input type="text" name="location" value="{{ old('location') }}" maxlength="1000">
            </label>
            <label>
                <span>参加者メールアドレス</span>
                <textarea
                    name="attendees"
                    rows="2"
                    placeholder="複数の場合はカンマまたは改行で区切る"
                >{{ old('attendees') }}</textarea>
            </label>
            <div class="gw-form__row">
                <label>
                    <span>繰り返し</span>
                    <select name="recurrence" required>
                        @foreach ([
                            'none' => 'なし',
                            'daily' => '毎日',
                            'weekly' => '毎週',
                            'monthly' => '毎月',
                            'yearly' => '毎年',
                        ] as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('recurrence', 'none') === $value)
                            >{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>繰り返し終了日</span>
                    <input type="date" name="recurrence_until" value="{{ old('recurrence_until') }}">
                </label>
            </div>
            <div class="gw-form__row">
                <label>
                    <span>タイムゾーン</span>
                    <input
                        type="text"
                        name="time_zone"
                        value="{{ old('time_zone', config('app.timezone')) }}"
                        required
                    >
                </label>
                <label>
                    <span>招待通知</span>
                    <select name="send_updates" required>
                        <option value="all">全参加者へ通知</option>
                        <option value="externalOnly">組織外のみ通知</option>
                        <option value="none">通知しない</option>
                    </select>
                </label>
            </div>
            <fieldset class="gw-fieldset">
                <legend>通知</legend>
                <label class="gw-check">
                    <input type="checkbox" name="reminder_minutes[]" value="10">
                    <span>10分前</span>
                </label>
                <label class="gw-check">
                    <input type="checkbox" name="reminder_minutes[]" value="60">
                    <span>1時間前</span>
                </label>
                <label class="gw-check">
                    <input type="checkbox" name="reminder_minutes[]" value="1440">
                    <span>1日前</span>
                </label>
            </fieldset>
            <label class="gw-check">
                <input type="checkbox" name="create_meet" value="1" @checked(old('create_meet'))>
                <span>Google Meetリンクを発行する</span>
            </label>
            <button class="gw-button gw-button--primary" type="submit">予定を作成</button>
        </form>
    </div>
</details>
