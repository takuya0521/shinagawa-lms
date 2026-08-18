@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-announcements-index')

@section('title', '掲示板管理')
@section('header-title', '掲示板管理')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ route('admin.announcements.create') }}"
                class="rounded-lg px-5 py-3 text-center font-semibold lms-button-primary"
            >
                お知らせを投稿
            </a>
        </section>

        <section class="p-6 lms-panel">
            <form method="GET" action="{{ route('admin.announcements.index') }}" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label
                            for="keyword"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >キーワード</label>
                        <input
                            id="keyword"
                            name="keyword"
                            type="search"
                            value="{{ $keyword }}"
                            class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                            placeholder="タイトル・本文"
                        >
                    </div>
                    <div>
                        <label
                            for="notice_type"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >種別</label>
                        <select
                            id="notice_type"
                            name="notice_type"
                            class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($noticeTypes as $noticeType)
                                <option
                                    value="{{ $noticeType->value }}"
                                    @selected($selectedNoticeType === $noticeType->value)
                                >{{ $noticeType->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-semibold lms-text-neutral-secondary">状態</label>
                        <select
                            id="status"
                            name="status"
                            class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($statuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected($selectedStatus === $status->value)
                                >{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-5">
                    <div>
                        <label for="target" class="block text-sm font-semibold lms-text-neutral-secondary">対象</label>
                        <select
                            id="target"
                            name="target"
                            class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($targetOptions as $value => $label)
                                <option value="{{ $value }}" @selected($selectedTarget === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            for="publish_from"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >掲載期間（開始）</label>
                        <input
                            id="publish_from"
                            name="publish_from"
                            type="date"
                            value="{{ $publishFrom }}"
                            class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                    </div>
                    <div>
                        <label
                            for="publish_to"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >掲載期間（終了）</label>
                        <input
                            id="publish_to"
                            name="publish_to"
                            type="date"
                            value="{{ $publishTo }}"
                            class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                    </div>
                    <div class="flex items-end">
                        <label
                            class="flex w-full items-center gap-2 rounded-lg border lms-border-neutral-default px-3
                                py-2.5 text-sm font-semibold"
                        >
                            <input type="checkbox" name="important_only" value="1" @checked($importantOnly)>
                            重要のみ
                        </label>
                    </div>
                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary"
                        >検索</button>
                        <a
                            href="{{ route('admin.announcements.index') }}"
                            class="rounded-lg border lms-border-neutral-default px-5 py-2.5 font-semibold"
                        >クリア</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">タイトル・種別</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">対象</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--date"
                            >掲載期間</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--status"
                            >状態</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action"
                            >操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($announcements as $announcement)
                            <tr>
                                <td class="px-5 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        @if ($announcement->is_important)
                                            <span
                                                class="inline-flex whitespace-nowrap rounded-full lms-bg-danger-muted
                                                    px-2.5 py-1 text-xs font-semibold
                                                    lms-text-danger-strong"
                                            >重要</span>
                                        @endif
                                        <p class="font-semibold lms-text-neutral-strong">{{ $announcement->title }}</p>
                                    </div>
                                    <p
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >{{ $announcement->notice_type->label() }} / 作成 {{ $announcement->creator->name
                                    }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="flex max-w-md flex-wrap gap-1.5">
                                        @foreach ($announcement->targets as $target)
                                            @php
                                                $label = match ($target->target_type) {
                                                    \App\Enums\AnnouncementTargetType::All => '全員',
                                                    \App\Enums\AnnouncementTargetType::Role =>
                                                    \App\Enums\UserRole::tryFrom((string)
                                                    $target->target_value)?->label() ?? '不明',
                                                    \App\Enums\AnnouncementTargetType::Grade =>
                                                    \App\Enums\Grade::tryFrom((string) $target->target_value)?->label()
                                                    ?? '不明',
                                                    \App\Enums\AnnouncementTargetType::ClassGroup =>
                                                    $classGroupNames[(int) $target->target_value] ?? '削除済みクラス',
                                                };
                                            @endphp
                                            <span
                                                class="inline-flex whitespace-nowrap rounded-full lms-bg-neutral-muted
                                                    px-2.5 py-1 text-xs font-semibold"
                                            >{{ $label }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <p>{{ $announcement->publish_start_at?->format('Y/m/d H:i') ?? '即時' }}</p>
                                    <p
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >～ {{ $announcement->publish_end_at?->format('Y/m/d H:i') ?? '無期限' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <span @class([
                                        'inline-flex whitespace-nowrap rounded-full',
                                        'px-3 py-1 text-xs font-semibold',
                                        'lms-bg-warning-muted lms-text-warning-strong' => $announcement->status ===
                                        \App\Enums\AnnouncementStatus::Draft,
                                        'lms-bg-success-muted lms-text-success-strong' => $announcement->status ===
                                        \App\Enums\AnnouncementStatus::Published,
                                        'lms-bg-neutral-muted lms-text-neutral-secondary' => $announcement->status ===
                                        \App\Enums\AnnouncementStatus::Archived,
                                    ])>
                                        {{ $announcement->status->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('admin.announcements.edit', $announcement) }}"
                                        class="font-semibold lms-link-primary"
                                    >編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    条件に一致するお知らせはありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($announcements->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">{{ $announcements->links() }}</div>
            @endif
        </section>
    </div>
@endsection
