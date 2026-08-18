{{-- 教員画面と生徒画面で同じ公開情報を表示し、ロール間でお知らせの見え方を揃える。 --}}
<div class="space-y-4">
    @forelse ($announcements as $announcement)
        <article class="p-6 lms-panel">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($announcement->is_important)
                            <span
                                class="rounded-full lms-bg-danger-muted px-2.5 py-1 text-xs font-semibold
                                    lms-text-danger-strong"
                            >重要</span>
                        @endif
                        <span
                            class="rounded-full lms-bg-neutral-muted px-2.5 py-1 text-xs font-semibold
                                lms-text-neutral-secondary"
                        >{{ $announcement->notice_type->label() }}</span>
                    </div>
                    <h2 class="mt-3 text-xl font-bold lms-text-neutral-strong">{{ $announcement->title }}</h2>
                    <p class="mt-2 text-sm lms-text-neutral-muted">
                        掲載開始：{{ $announcement->publish_start_at?->format('Y年m月d日 H:i') ?? '即時' }}
                    </p>
                </div>
                <a
                    href="{{ route($showRoute, $announcement) }}"
                    class="shrink-0 rounded-lg border lms-border-neutral-default px-4 py-2 text-center text-sm
                        font-semibold lms-hover-bg-neutral-subtle"
                >詳細を見る</a>
            </div>
            <p
                class="mt-4 line-clamp-3 whitespace-pre-line text-sm leading-7 lms-text-neutral-subtle"
            >{{ $announcement->body }}</p>
        </article>
    @empty
        <div class="px-6 py-12 text-center text-sm lms-text-neutral-muted lms-panel">
            条件に一致する掲載中のお知らせはありません。
        </div>
    @endforelse
</div>

@if ($announcements->hasPages())
    <div class="mt-6">{{ $announcements->links() }}</div>
@endif
