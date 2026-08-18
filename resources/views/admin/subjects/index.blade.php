@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-subjects-index')

@section('title', '科目管理')
@section('header-title', '科目管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('admin.subjects.create') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-3 font-semibold
                    lms-button-primary"
            >
                科目を登録
            </a>
        </div>

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.subjects.index') }}"
                class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto_auto]"
            >
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
                        value="{{ $keyword }}"
                        placeholder="科目コード・科目名"
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                            lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                    >
                </div>

                <div>
                    <label
                        for="status"
                        class="block text-sm font-semibold lms-text-neutral-secondary"
                    >
                        状態
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                            lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                    >
                        <option value="">
                            すべて
                        </option>

                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected(
                                    $selectedStatus === $status->value
                                )
                            >
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg px-5 py-2.5 font-semibold
                            lms-button-primary"
                    >
                        検索
                    </button>
                </div>

                <div class="flex items-end">
                    <a
                        href="{{ route('admin.subjects.index') }}"
                        class="inline-flex w-full items-center justify-center rounded-lg border
                            lms-border-neutral-default lms-bg-surface px-5 py-2.5 font-semibold
                            lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                    >
                        クリア
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--medium"
                            >
                                科目コード
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted"
                            >
                                科目名
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--status"
                            >
                                状態
                            </th>

                            <th
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--action"
                            >
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle lms-bg-surface">
                        @forelse ($subjects as $subject)
                            <tr class="lms-hover-bg-neutral-subtle">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold lms-text-neutral-strong">
                                    {{ $subject->subject_code }}
                                </td>

                                <td class="px-6 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $subject->subject_name }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
                                            'px-2.5 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success-strong' => $subject->status ===
                                            \App\Enums\MasterStatus::Active,
                                            'lms-bg-neutral-muted lms-text-neutral-secondary' => $subject->status ===
                                            \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $subject->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <a
                                        href="{{ route(
                                            'admin.subjects.edit',
                                            $subject,
                                        ) }}"
                                        class="font-semibold lms-link-primary"
                                    >
                                        編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-6 py-12 text-center text-sm lms-text-neutral-muted"
                                >
                                    条件に一致する科目はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($subjects->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $subjects->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
