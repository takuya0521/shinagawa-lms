@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-class-groups-index')

@section('title', 'クラス管理')
@section('header-title', 'クラス管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <div class="flex items-center gap-4">
                <p class="text-sm lms-text-neutral-muted">
                    {{ number_format($classGroups->total()) }}件
                </p>

                <a
                    href="{{ route('admin.class-groups.create') }}"
                    class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                >
                    クラス登録
                </a>
            </div>
        </header>

        <section class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.class-groups.index') }}"
                class="grid gap-4 md:grid-cols-3"
            >
                <div class="md:col-span-2">
                    <label
                        for="keyword"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        コード・クラス名・説明
                    </label>

                    <input
                        id="keyword"
                        name="keyword"
                        type="search"
                        value="{{ request('keyword') }}"
                        placeholder="検索キーワード"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                </div>

                <div>
                    <label
                        for="status"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        状態
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>

                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected(
                                    request('status')
                                    === $status->value
                                )
                            >
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 md:col-span-3">
                    <button
                        type="submit"
                        class="rounded-lg px-5 py-2 font-semibold lms-button-primary"
                    >
                        検索
                    </button>

                    <a
                        href="{{ route('admin.class-groups.index') }}"
                        class="rounded-lg border lms-border-neutral-default px-5 py-2 font-semibold
                            lms-hover-bg-neutral-subtle"
                    >
                        クリア
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--compact">
                                コード
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                クラス名
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                説明
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--medium">
                                所属生徒数
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--status">
                                状態
                            </th>

                            <th class="px-5 py-3 text-right text-sm font-semibold lms-table-col--action">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-faint">
                        @forelse ($classGroups as $classGroup)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 font-mono text-sm">
                                    {{ $classGroup->class_code }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 font-medium">
                                    {{ $classGroup->class_name }}
                                </td>

                                <td class="max-w-md px-5 py-4 text-sm lms-text-neutral-subtle">
                                    {{ $classGroup->description ?? '未設定' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ number_format($classGroup->students_count) }}名
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
                                            'px-3 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success' => $classGroup->status ===
                                            \App\Enums\MasterStatus::Active,
                                            'lms-bg-neutral-disabled lms-text-neutral-secondary' => $classGroup->status
                                            === \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $classGroup->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route(
                                            'admin.class-groups.edit',
                                            $classGroup,
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
                                    colspan="6"
                                    class="px-6 py-12 text-center lms-text-neutral-muted"
                                >
                                    条件に一致するクラスはありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($classGroups->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $classGroups->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
