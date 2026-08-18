@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-operation-logs-index')

@section('title', '操作ログ')
@section('header-title', '操作ログ')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">

            <a
                href="{{ route(
                    'admin.operation-logs.export',
                    request()->query(),
                ) }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg border lms-border-neutral-default
                    px-5 py-3 font-semibold lms-hover-bg-neutral-subtle"
            >
                検索結果をCSV出力
            </a>
        </div>

        @error('export')
            <div
                class="rounded-xl border lms-border-danger lms-bg-danger-soft px-5 py-4 text-sm font-semibold
                    lms-text-danger-strong"
            >
                {{ $message }}
            </div>
        @enderror

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.operation-logs.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label
                            for="date_from"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            操作日（開始）
                        </label>

                        <input
                        class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3
                            py-2"
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="{{ $dateFrom->format('Y-m-d') }}"
                        >
                    </div>

                    <div>
                        <label
                            for="date_to"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            操作日（終了）
                        </label>

                        <input
                        class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3
                            py-2"
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $dateTo->format('Y-m-d') }}"
                        >
                    </div>

                    <div>
                        <label
                            for="user_id"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            操作者
                        </label>

                        <select
                            id="user_id"
                            name="user_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>

                            @foreach ($users as $user)
                                <option
                                    value="{{ $user->id }}"
                                    @selected($selectedUserId === $user->id)
                                >
                                    {{ $user->name }} / {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="action"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            操作
                        </label>

                        <select
                            id="action"
                            name="action"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>

                            @foreach ($actions as $action)
                                <option
                                    value="{{ $action['value'] }}"
                                    @selected(
                                        $selectedAction
                                        === $action['value']
                                    )
                                >
                                    {{ $action['label'] }}
                                    （{{ $action['value'] }}）
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label
                            for="target_table"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            対象
                        </label>

                        <select
                            id="target_table"
                            name="target_table"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>

                            @foreach ($targetTables as $targetTable)
                                <option
                                    value="{{ $targetTable['value'] }}"
                                    @selected(
                                        $selectedTargetTable
                                        === $targetTable['value']
                                    )
                                >
                                    {{ $targetTable['label'] }}
                                    （{{ $targetTable['value'] }}）
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="target_id"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            対象ID
                        </label>

                        <input
                            id="target_id"
                            name="target_id"
                            type="number"
                            min="1"
                            value="{{ $selectedTargetId }}"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                    </div>

                    <div>
                        <label
                            for="keyword"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            キーワード
                        </label>

                        <input
                            id="keyword"
                            name="keyword"
                            type="search"
                            maxlength="100"
                            value="{{ $keyword }}"
                            placeholder="氏名・メール・操作・詳細"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                    </div>

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary"
                        >
                            検索
                        </button>

                        <a
                            href="{{ route('admin.operation-logs.index') }}"
                            class="rounded-lg border lms-border-neutral-default px-5 py-2.5 font-semibold
                                lms-hover-bg-neutral-subtle"
                        >
                            クリア
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--date"
                            >
                                操作日時
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">
                                操作者
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >
                                操作
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">
                                対象
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">
                                概要
                            </th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action"
                            >
                                詳細
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($operationLogs as $operationLog)
                            <tr
                                class="align-top lms-hover-bg-neutral-subtle"
                                data-operation-log-id="{{ $operationLog->id }}"
                            >
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">
                                        {{ $operationLog->created_at->format('Y/m/d') }}
                                    </p>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $operationLog->created_at->format('H:i:s') }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">
                                        {{ $operationLog->user?->name ?? '削除済みユーザー / システム' }}
                                    </p>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $operationLog->user?->email ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">
                                        {{ \App\Support\OperationLogPresenter::actionLabel(
                                            $operationLog->action,
                                        ) }}
                                    </p>
                                    <p class="mt-1 font-mono text-xs lms-text-neutral-muted">
                                        {{ $operationLog->action }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">
                                        {{ \App\Support\OperationLogPresenter::targetLabel(
                                            $operationLog->target_table,
                                        ) }}
                                    </p>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $operationLog->target_table ?? '-' }}
                                        /
                                        ID {{ $operationLog->target_id ?? '-' }}
                                    </p>
                                </td>

                                <td class="max-w-sm px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ \App\Support\OperationLogPresenter::detailSummary(
                                        $operationLog,
                                    ) }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route(
                                            'admin.operation-logs.show',
                                            $operationLog,
                                        ) }}"
                                        class="font-semibold lms-link-primary"
                                    >
                                        確認
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center text-sm lms-text-neutral-muted"
                                >
                                    条件に一致する操作ログはありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($operationLogs->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $operationLogs->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
