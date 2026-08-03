{-- 教員画面と生徒画面で同じ公開情報を表示し、ロール間でお知らせの見え方を揃える。 --}
<div class="space-y-4">
    @forelse ($announcements as $announcement)
        <article class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($announcement->is_important)
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">重要</span>
                        @endif
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $announcement->notice_type->label() }}</span>
                    </div>
                    <h2 class="mt-3 text-xl font-bold text-slate-900">{{ $announcement->title }}</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        掲載開始：{{ $announcement->publish_start_at?->format('Y年m月d日 H:i') ?? '即時' }}
                    </p>
                </div>
                <a href="{{ route($showRoute, $announcement) }}" class="shrink-0 rounded-lg border border-slate-300 px-4 py-2 text-center text-sm font-semibold hover:bg-slate-50">詳細を見る</a>
            </div>
            <p class="mt-4 line-clamp-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $announcement->body }}</p>
        </article>
    @empty
        <div class="rounded-2xl bg-white px-6 py-12 text-center text-sm text-slate-500 shadow-sm">
            条件に一致する掲載中のお知らせはありません。
        </div>
    @endforelse
</div>

@if ($announcements->hasPages())
    <div class="mt-6">{{ $announcements->links() }}</div>
@endif
