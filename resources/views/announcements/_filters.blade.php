{-- 教員画面と生徒画面で検索条件を共有し、同じ絞り込み条件を提供する。 --}
<form method="GET" action="{{ $action }}" class="grid gap-4 lg:grid-cols-4">
    <div class="lg:col-span-2">
        <label for="keyword" class="block text-sm font-semibold text-slate-700">キーワード</label>
        <input id="keyword" name="keyword" type="search" value="{{ $keyword }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="タイトル・本文">
    </div>
    <div>
        <label for="notice_type" class="block text-sm font-semibold text-slate-700">種別</label>
        <select id="notice_type" name="notice_type" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">すべて</option>
            @foreach ($noticeTypes as $noticeType)
                <option value="{{ $noticeType->value }}" @selected($selectedNoticeType === $noticeType->value)>{{ $noticeType->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end gap-3">
        <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold">
            <input type="checkbox" name="important_only" value="1" @checked($importantOnly)>
            重要のみ
        </label>
        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button>
    </div>
</form>
