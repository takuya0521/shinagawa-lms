<details class="gw-new-menu__section">
    <summary>
        <x-google-icon name="plus" :size="20" />
        <span>カレンダー</span>
    </summary>
    <div class="gw-calendar-create-form">
        <form class="gw-form" method="POST" action="{{ route('google-workspace.calendar.calendars.store') }}">
            @csrf
            <label>
                <span>カレンダー名</span>
                <input type="text" name="summary" value="{{ old('summary') }}" required maxlength="255">
            </label>
            <label>
                <span>説明</span>
                <textarea name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea>
            </label>
            <label>
                <span>タイムゾーン</span>
                <input
                    type="text"
                    name="time_zone"
                    value="{{ old('time_zone', config('app.timezone')) }}"
                    required
                >
            </label>
            <button class="gw-button gw-button--primary" type="submit">カレンダーを作成</button>
        </form>
    </div>
</details>
