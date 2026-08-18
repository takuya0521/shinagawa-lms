<section class="gw-toolbar">
    <form class="gw-search gw-search--calendar" method="GET" action="{{ route('google-workspace.calendar.index') }}">
        <label>
            <span>カレンダー</span>
            <select name="calendar_id">
                @foreach ($calendars as $calendar)
                    <option value="{{ $calendar->id }}" @selected($calendar->id === $calendarId)>
                        {{ $calendar->summary }}{{ $calendar->primary ? '（メイン）' : '' }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            <span>キーワード</span>
            <input type="search" name="keyword" value="{{ $keyword }}" placeholder="予定名・説明・場所">
        </label>
        <label>
            <span>開始日</span>
            <input type="date" name="date_from" value="{{ $dateFrom }}">
        </label>
        <label>
            <span>終了日</span>
            <input type="date" name="date_to" value="{{ $dateTo }}">
        </label>
        <button class="gw-button gw-button--secondary" type="submit">表示</button>
    </form>
</section>
