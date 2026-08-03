@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/operation-logs/index.css')
@section('page-class', 'page-pattern-list page-admin-operation-logs-index')

@section('title', '操作ログ')
@section('header-title', '操作ログ')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    NFR-004 / CP-006
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    操作ログ
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    重要な登録・更新・削除・設定変更・データ出力の履歴を確認します。
                </p>
            </div>

            <a
                href="{{ route(
                    'admin.operation-logs.export',
                    request()->query(),
                ) }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
            >
                検索結果をCSV出力
            </a>
        </div>

        @error('export')
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                {{ $message }}
            </div>
        @enderror

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.operation-logs.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label
                            for="date_from"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            操作日（開始）
                        </label>

                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="{{ $dateFrom->format('Y-m-d') }}"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </div>

                    <div>
                        <label
                            for="date_to"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            操作日（終了）
                        </label>

                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $dateTo->format('Y-m-d') }}"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </div>

                    <div>
                        <label
                            for="user_id"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            操作者
                        </label>

                        <select
                            id="user_id"
                            name="user_id"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
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
                            class="block text-sm font-semibold text-slate-700"
                        >
                            操作
                        </label>

                        <select
                            id="action"
                            name="action"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
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
                            class="block text-sm font-semibold text-slate-700"
                        >
                            対象
                        </label>

                        <select
                            id="target_table"
                            name="target_table"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
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
                            class="block text-sm font-semibold text-slate-700"
                        >
                            対象ID
                        </label>

                        <input
                            id="target_id"
                            name="target_id"
                            type="number"
                            min="1"
                            value="{{ $selectedTargetId }}"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </div>

                    <div>
                        <label
                            for="keyword"
                            class="block text-sm font-semibold text-slate-700"
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
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </div>

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700"
                        >
                            検索
                        </button>

                        <a
                            href="{{ route('admin.operation-logs.index') }}"
                            class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold hover:bg-slate-50"
                        >
                            クリア
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">
                                操作日時
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">
                                操作者
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">
                                操作
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">
                                対象
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">
                                概要
                            </th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">
                                詳細
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @forelse ($operationLogs as $operationLog)
                            <tr
                                class="align-top hover:bg-slate-50"
                                data-operation-log-id="{{ $operationLog->id }}"
                            >
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">
                                        {{ $operationLog->created_at->format('Y/m/d') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $operationLog->created_at->format('H:i:s') }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">
                                        {{ $operationLog->user?->name ?? '削除済みユーザー / システム' }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $operationLog->user?->email ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">
                                        {{ \App\Support\OperationLogPresenter::actionLabel(
                                            $operationLog->action,
                                        ) }}
                                    </p>
                                    <p class="mt-1 font-mono text-xs text-slate-500">
                                        {{ $operationLog->action }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">
                                        {{ \App\Support\OperationLogPresenter::targetLabel(
                                            $operationLog->target_table,
                                        ) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $operationLog->target_table ?? '-' }}
                                        /
                                        ID {{ $operationLog->target_id ?? '-' }}
                                    </p>
                                </td>

                                <td class="max-w-sm px-5 py-4 text-sm text-slate-700">
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
                                        class="font-semibold text-blue-700 hover:text-blue-900"
                                    >
                                        確認
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center text-sm text-slate-500"
                                >
                                    条件に一致する操作ログはありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($operationLogs->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $operationLogs->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
