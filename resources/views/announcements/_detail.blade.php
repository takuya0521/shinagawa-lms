{-- 教員画面と生徒画面で本文表示を共有し、公開日時と重要区分の表記差異を防ぐ。 --}
<article class="rounded-2xl bg-white p-8 shadow-sm">
    <div class="flex flex-wrap items-center gap-2">
        @if ($announcement->is_important)
            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">重要</span>
        @endif
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $announcement->notice_type->label() }}</span>
    </div>

    <h1 class="mt-4 text-3xl font-bold text-slate-900">{{ $announcement->title }}</h1>

    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-500">
        <span>掲載開始：{{ $announcement->publish_start_at?->format('Y年m月d日 H:i') ?? '即時' }}</span>
        @if ($announcement->publish_end_at !== null)
            <span>掲載終了：{{ $announcement->publish_end_at->format('Y年m月d日 H:i') }}</span>
        @endif
    </div>

    <div class="mt-8 whitespace-pre-line border-t border-slate-200 pt-8 leading-8 text-slate-700">{{ $announcement->body }}</div>
</article>
