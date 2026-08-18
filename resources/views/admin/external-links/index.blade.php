@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-external-links-index')

@section('title', '外部リンク管理')
@section('header-title', '外部リンク管理')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ route('admin.external-links.create') }}"
                class="rounded-lg px-5 py-3 text-center font-semibold lms-button-primary"
            >外部リンクを登録</a>
        </section>

        <section class="p-6 lms-panel">
            <form method="GET" action="{{ route('admin.external-links.index') }}" class="grid gap-4 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="keyword" class="block text-sm font-semibold lms-text-neutral-secondary">キーワード</label>
                    <input
                        id="keyword"
                        name="keyword"
                        type="search"
                        value="{{ $keyword }}"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                        placeholder="リンク名・URL"
                    >
                </div>
                <div>
                    <label for="link_type" class="block text-sm font-semibold lms-text-neutral-secondary">リンク種別</label>
                    <select
                        id="link_type"
                        name="link_type"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>
                        @foreach ($linkTypes as $linkType)
                            <option
                                value="{{ $linkType->value }}"
                                @selected($selectedLinkType === $linkType->value)
                            >{{ $linkType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="scope_type" class="block text-sm font-semibold lms-text-neutral-secondary">公開範囲</label>
                    <select
                        id="scope_type"
                        name="scope_type"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>
                        @foreach ($scopeTypes as $scopeType)
                            <option
                                value="{{ $scopeType->value }}"
                                @selected($selectedScopeType === $scopeType->value)
                            >{{ $scopeType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold lms-text-neutral-secondary">状態</label>
                    <select id="status" name="status" class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control">
                        <option value="">すべて</option>
                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected($selectedStatus === $status->value)
                            >{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-5 flex justify-end gap-3">
                    <a
                        href="{{ route('admin.external-links.index') }}"
                        class="rounded-lg border lms-border-neutral-default px-5 py-2.5 font-semibold"
                    >クリア</a>
                    <button type="submit" class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary">検索</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle"><tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">リンク</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">公開範囲</th>
                        <th
                            class="px-5 py-3 text-center text-xs font-semibold lms-text-neutral-muted
                                lms-table-col--compact"
                        >表示順</th>
                        <th
                            class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                lms-table-col--status"
                        >状態</th>
                        <th
                            class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                lms-table-col--action"
                        >操作</th>
                    </tr></thead>
                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($externalLinks as $externalLink)
                            @php
                                $scopeLabel = $externalLink->scope_type === \App\Enums\ExternalLinkScopeType::Global
                                    ? '全体'
                                    : ($scopeLabels[$externalLink->scope_type->value][$externalLink->scope_id] ??
                                    '対象不明');
                            @endphp
                            <tr>
                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">{{ $externalLink->link_name }}</p>
                                    <p
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >{{ $externalLink->link_type->label() }}</p>
                                    <a
                                        href="{{ $externalLink->url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 block max-w-md truncate text-xs font-semibold lms-link-primary"
                                    >{{ $externalLink->url }}</a>
                                </td>
                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <p>{{ $externalLink->scope_type->label() }}</p>
                                    @if ($externalLink->scope_type !== \App\Enums\ExternalLinkScopeType::Global)
                                        <p class="mt-1 text-xs lms-text-neutral-muted">{{ $scopeLabel }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center text-sm">{{ $externalLink->display_order }}</td>
                                <td class="px-5 py-4 text-sm">
                                    <span @class([
                                        'inline-flex whitespace-nowrap rounded-full',
                                        'px-3 py-1 text-xs font-semibold',
                                        'lms-bg-success-muted lms-text-success-strong' => $externalLink->status ===
                                        \App\Enums\MasterStatus::Active,
                                        'lms-bg-neutral-muted lms-text-neutral-secondary' => $externalLink->status ===
                                        \App\Enums\MasterStatus::Inactive,
                                    ])>{{ $externalLink->status->label() }}</span>
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm"
                                >
                                <a
                                    href="{{ route('admin.external-links.edit', $externalLink) }}"
                                    class="font-semibold lms-link-primary"
                                >編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    条件に一致する外部リンクはありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($externalLinks->hasPages())
                <div class="border-t px-6 py-4 lms-border-neutral-subtle">
                    {{ $externalLinks->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
