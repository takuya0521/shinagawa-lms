@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/operation-logs/show.css')
@section('page-class', 'page-pattern-detail page-admin-operation-logs-show')

@section('title', '操作ログ詳細')
@section('header-title', '操作ログ')

@section('content')
    <section class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">
                NFR-004 / CP-006
            </p>

            <div class="mt-1 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        操作ログ詳細
                    </h1>

                    <p class="mt-2 text-sm text-slate-600">
                        ログID {{ $operationLog->id }} の記録内容を表示します。操作ログは画面から変更・削除できません。
                    </p>
                </div>

                <a
                    href="{{ route('admin.operation-logs.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 px-5 py-2.5 font-semibold hover:bg-slate-50"
                >
                    一覧へ戻る
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">
                    操作情報
                </h2>

                <dl class="mt-5 divide-y divide-slate-200">
                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold text-slate-500">
                            操作日時
                        </dt>
                        <dd class="text-sm text-slate-900 sm:col-span-2">
                            {{ $operationLog->created_at->format('Y/m/d H:i:s') }}
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold text-slate-500">
                            操作者
                        </dt>
                        <dd class="text-sm text-slate-900 sm:col-span-2">
                            {{ $operationLog->user?->name ?? '削除済みユーザー / システム' }}
                            @if ($operationLog->user !== null)
                                <span class="ml-1 text-slate-500">
                                    （{{ $operationLog->user->email }}）
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold text-slate-500">
                            操作
                        </dt>
                        <dd class="text-sm text-slate-900 sm:col-span-2">
                            <p class="font-semibold">
                                {{ \App\Support\OperationLogPresenter::actionLabel(
                                    $operationLog->action,
                                ) }}
                            </p>
                            <p class="mt-1 font-mono text-xs text-slate-500">
                                {{ $operationLog->action }}
                            </p>
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold text-slate-500">
                            対象
                        </dt>
                        <dd class="text-sm text-slate-900 sm:col-span-2">
                            <p class="font-semibold">
                                {{ \App\Support\OperationLogPresenter::targetLabel(
                                    $operationLog->target_table,
                                ) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $operationLog->target_table ?? '-' }}
                                /
                                ID {{ $operationLog->target_id ?? '-' }}
                            </p>
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold text-slate-500">
                            IPアドレス
                        </dt>
                        <dd class="text-sm text-slate-900 sm:col-span-2">
                            {{ data_get(
                                $operationLog->detail,
                                'ip_address',
                                '-',
                            ) }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">
                    記録詳細
                </h2>

                @if (($operationLog->detail ?? []) === [])
                    <p class="mt-5 rounded-xl bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                        詳細情報はありません。
                    </p>
                @else
                    <pre class="mt-5 max-h-[36rem] overflow-auto rounded-xl bg-slate-950 p-5 text-xs leading-6 text-slate-100">{{ json_encode(
                        $operationLog->detail,
                        JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES,
                    ) }}</pre>
                @endif
            </section>
        </div>
    </section>
@endsection
