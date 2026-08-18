{{-- 教員画面と生徒画面で本文表示を共有し、公開日時と重要区分の表記差異を防ぐ。 --}}
<article class="p-8 lms-panel">
    <div class="flex flex-wrap items-center gap-2">
        @if ($announcement->is_important)
            <span
                class="rounded-full lms-bg-danger-muted px-3 py-1 text-xs font-semibold lms-text-danger-strong"
            >重要</span>
        @endif
        <span
            class="rounded-full lms-bg-neutral-muted px-3 py-1 text-xs font-semibold lms-text-neutral-secondary"
        >{{ $announcement->notice_type->label() }}</span>
    </div>

    <h1 class="mt-4 text-3xl font-bold lms-text-neutral-strong">{{ $announcement->title }}</h1>

    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm lms-text-neutral-muted">
        <span>掲載開始：{{ $announcement->publish_start_at?->format('Y年m月d日 H:i') ?? '即時' }}</span>
        @if ($announcement->publish_end_at !== null)
            <span>掲載終了：{{ $announcement->publish_end_at->format('Y年m月d日 H:i') }}</span>
        @endif
    </div>

    <div
        class="mt-8 whitespace-pre-line border-t lms-border-neutral-subtle pt-8 leading-8 lms-text-neutral-secondary"
    >{{ $announcement->body }}</div>
</article>
