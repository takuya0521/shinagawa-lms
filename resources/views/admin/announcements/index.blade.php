@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/announcements/index.css')
@section('page-class', 'page-pattern-list page-admin-announcements-index')

@section('title', '掲示板管理')
@section('header-title', '掲示板管理')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">A-034</p>
                <h1 class="mt-1 text-2xl font-bold">掲示板管理</h1>
                <p class="mt-2 text-sm text-slate-600">下書き・公開・掲載終了を含むお知らせを管理します。</p>
            </div>
            <a href="{{ route('admin.announcements.create') }}" class="rounded-lg bg-slate-900 px-5 py-3 text-center font-semibold text-white hover:bg-slate-700">
                お知らせを投稿
            </a>
        </section>


        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.announcements.index') }}" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-4">
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
                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700">状態</label>
                        <select id="status" name="status" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-5">
                    <div>
                        <label for="target" class="block text-sm font-semibold text-slate-700">対象</label>
                        <select id="target" name="target" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($targetOptions as $value => $label)
                                <option value="{{ $value }}" @selected($selectedTarget === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="publish_from" class="block text-sm font-semibold text-slate-700">掲載期間（開始）</label>
                        <input id="publish_from" name="publish_from" type="date" value="{{ $publishFrom }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label for="publish_to" class="block text-sm font-semibold text-slate-700">掲載期間（終了）</label>
                        <input id="publish_to" name="publish_to" type="date" value="{{ $publishTo }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="flex items-end">
                        <label class="flex w-full items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold">
                            <input type="checkbox" name="important_only" value="1" @checked($importantOnly)>
                            重要のみ
                        </label>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button>
                        <a href="{{ route('admin.announcements.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold">クリア</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">タイトル・種別</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">対象</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">掲載期間</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">状態</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($announcements as $announcement)
                            <tr>
                                <td class="px-5 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        @if ($announcement->is_important)
                                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">重要</span>
                                        @endif
                                        <p class="font-semibold text-slate-900">{{ $announcement->title }}</p>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $announcement->notice_type->label() }} / 作成 {{ $announcement->creator->name }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <div class="flex max-w-md flex-wrap gap-1.5">
                                        @foreach ($announcement->targets as $target)
                                            @php
                                                $label = match ($target->target_type) {
                                                    \App\Enums\AnnouncementTargetType::All => '全員',
                                                    \App\Enums\AnnouncementTargetType::Role => \App\Enums\UserRole::tryFrom((string) $target->target_value)?->label() ?? '不明',
                                                    \App\Enums\AnnouncementTargetType::Grade => \App\Enums\Grade::tryFrom((string) $target->target_value)?->label() ?? '不明',
                                                    \App\Enums\AnnouncementTargetType::ClassGroup => $classGroupNames[(int) $target->target_value] ?? '削除済みクラス',
                                                };
                                            @endphp
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ $label }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    <p>{{ $announcement->publish_start_at?->format('Y/m/d H:i') ?? '即時' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">～ {{ $announcement->publish_end_at?->format('Y/m/d H:i') ?? '無期限' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <span @class([
                                        'rounded-full px-3 py-1 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $announcement->status === \App\Enums\AnnouncementStatus::Draft,
                                        'bg-emerald-100 text-emerald-800' => $announcement->status === \App\Enums\AnnouncementStatus::Published,
                                        'bg-slate-100 text-slate-700' => $announcement->status === \App\Enums\AnnouncementStatus::Archived,
                                    ])>
                                        {{ $announcement->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-sm">
                                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="font-semibold text-blue-700 hover:text-blue-900">編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">条件に一致するお知らせはありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($announcements->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">{{ $announcements->links() }}</div>
            @endif
        </section>
    </div>
@endsection
