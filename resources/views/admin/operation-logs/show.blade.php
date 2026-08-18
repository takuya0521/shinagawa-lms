@extends('layouts.app')

@section('page-class', 'page-pattern-detail page-admin-operation-logs-show')

@section('title', '操作ログ詳細')
@section('header-title', '操作ログ')

@section('content')
    <section class="space-y-6">
        <div class="p-6 lms-panel">

            <div class="mt-1 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold lms-text-neutral-strong">
                        操作ログ詳細
                    </h1>

                </div>

                <a
                    href="{{ route('admin.operation-logs.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg border
                        lms-border-neutral-default px-5 py-2.5 font-semibold lms-hover-bg-neutral-subtle"
                >
                    一覧へ戻る
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="p-6 lms-panel">
                <h2 class="text-lg font-bold lms-text-neutral-strong">
                    操作情報
                </h2>

                <dl class="mt-5 divide-y lms-divide-neutral-subtle">
                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold lms-text-neutral-muted">
                            操作日時
                        </dt>
                        <dd class="text-sm lms-text-neutral-strong sm:col-span-2">
                            {{ $operationLog->created_at->format('Y/m/d H:i:s') }}
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold lms-text-neutral-muted">
                            操作者
                        </dt>
                        <dd class="text-sm lms-text-neutral-strong sm:col-span-2">
                            {{ $operationLog->user?->name ?? '削除済みユーザー / システム' }}
                            @if ($operationLog->user !== null)
                                <span class="ml-1 lms-text-neutral-muted">
                                    （{{ $operationLog->user->email }}）
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold lms-text-neutral-muted">
                            操作
                        </dt>
                        <dd class="text-sm lms-text-neutral-strong sm:col-span-2">
                            <p class="font-semibold">
                                {{ \App\Support\OperationLogPresenter::actionLabel(
                                    $operationLog->action,
                                ) }}
                            </p>
                            <p class="mt-1 font-mono text-xs lms-text-neutral-muted">
                                {{ $operationLog->action }}
                            </p>
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold lms-text-neutral-muted">
                            対象
                        </dt>
                        <dd class="text-sm lms-text-neutral-strong sm:col-span-2">
                            <p class="font-semibold">
                                {{ \App\Support\OperationLogPresenter::targetLabel(
                                    $operationLog->target_table,
                                ) }}
                            </p>
                            <p class="mt-1 text-xs lms-text-neutral-muted">
                                {{ $operationLog->target_table ?? '-' }}
                                /
                                ID {{ $operationLog->target_id ?? '-' }}
                            </p>
                        </dd>
                    </div>

                    <div class="grid gap-1 py-3 sm:grid-cols-3">
                        <dt class="text-sm font-semibold lms-text-neutral-muted">
                            IPアドレス
                        </dt>
                        <dd class="text-sm lms-text-neutral-strong sm:col-span-2">
                            {{ data_get(
                                $operationLog->detail,
                                'ip_address',
                                '-',
                            ) }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="p-6 lms-panel">
                <h2 class="text-lg font-bold lms-text-neutral-strong">
                    記録詳細
                </h2>

                @if (($operationLog->detail ?? []) === [])
                    <p
                        class="mt-5 rounded-xl lms-bg-neutral-subtle px-5 py-8 text-center text-sm
                            lms-text-neutral-muted"
                    >
                        詳細情報はありません。
                    </p>
                @else
                    <pre
                        class="mt-5 max-h-[36rem] overflow-auto rounded-xl lms-bg-neutral-dark p-5 text-xs leading-6
                            lms-text-on-dark"
                    >{{ json_encode(
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
