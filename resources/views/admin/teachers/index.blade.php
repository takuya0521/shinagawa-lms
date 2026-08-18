@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-teachers-index')

@section('title', '教員管理')
@section('header-title', '教員管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <div class="flex items-center gap-4">
                <p class="text-sm lms-text-neutral-muted">
                    {{ number_format($teachers->total()) }}件
                </p>

                <a
                    href="{{ route('admin.teachers.create') }}"
                    class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                >
                    教員登録
                </a>
            </div>
        </header>

        <section class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.teachers.index') }}"
                class="grid gap-4 lg:grid-cols-3"
            >
                <div>
                    <label
                        for="keyword"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        氏名・メール・担当科目メモ
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
                        for="teacher_status"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        教員状態
                    </label>

                    <select
                        id="teacher_status"
                        name="teacher_status"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>

                        @foreach ($teacherStatuses as $teacherStatus)
                            <option
                                value="{{ $teacherStatus->value }}"
                                @selected(
                                    request('teacher_status')
                                    === $teacherStatus->value
                                )
                            >
                                {{ $teacherStatus->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="account_status"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        アカウント利用状態
                    </label>

                    <select
                        id="account_status"
                        name="account_status"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>

                        @foreach ($accountStatuses as $accountStatus)
                            <option
                                value="{{ $accountStatus->value }}"
                                @selected(
                                    request('account_status')
                                    === $accountStatus->value
                                )
                            >
                                {{ $accountStatus->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 lg:col-span-3">
                    <button
                        type="submit"
                        class="rounded-lg px-5 py-2 font-semibold lms-button-primary"
                    >
                        検索
                    </button>

                    <a
                        href="{{ route('admin.teachers.index') }}"
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
                                ID
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                氏名
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                メールアドレス
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                担当科目メモ
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--status">
                                教員状態
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--status">
                                利用状態
                            </th>

                            <th class="px-5 py-3 text-right text-sm font-semibold lms-table-col--action">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-faint">
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm lms-text-neutral-muted">
                                    {{ $teacher->id }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 font-medium">
                                    {{ $teacher->user->name }}
                                </td>

                                <td class="lms-table-cell--email px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $teacher->user->email }}
                                </td>

                                <td class="lms-table-cell--long px-5 py-4 text-sm lms-text-neutral-subtle">
                                    {{ $teacher->subject_notes ?? '未設定' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
                                            'px-3 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success' => $teacher->status ===
                                            \App\Enums\MasterStatus::Active,
                                            'lms-bg-neutral-disabled lms-text-neutral-secondary' => $teacher->status
                                            === \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $teacher->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
                                            'px-3 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success' => $teacher->user->status ===
                                            \App\Enums\UserStatus::Active,
                                            'lms-bg-neutral-disabled lms-text-neutral-secondary' =>
                                            $teacher->user->status === \App\Enums\UserStatus::Suspended,
                                        ])
                                    >
                                        {{ $teacher->user->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        <a
                                            href="{{ route(
                                                'admin.teachers.show',
                                                $teacher,
                                            ) }}"
                                            class="font-semibold lms-link-primary"
                                        >
                                            詳細
                                        </a>

                                        <a
                                            href="{{ route(
                                                'admin.teachers.edit',
                                                $teacher,
                                            ) }}"
                                            class="font-semibold lms-link-primary"
                                        >
                                            編集
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center lms-text-neutral-muted"
                                >
                                    条件に一致する教員はいません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($teachers->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $teachers->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
