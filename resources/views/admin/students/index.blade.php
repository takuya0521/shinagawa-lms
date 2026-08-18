@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-students-index')

@section('title', '生徒管理')
@section('header-title', '生徒管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <div class="flex items-center gap-4">
                <p class="text-sm lms-text-neutral-muted">
                    {{ number_format($students->total()) }}件
                </p>

                <a
                    href="{{ route('admin.students.create') }}"
                    class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                >
                    生徒登録
                </a>
            </div>
        </header>

        <section class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.students.index') }}"
                class="grid gap-4 lg:grid-cols-3"
            >
                <div>
                    <label
                        for="keyword"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        生徒番号・氏名・メール・提携校
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
                        for="grade"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        学年
                    </label>

                    <select
                        id="grade"
                        name="grade"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">
                            すべて
                        </option>

                        @foreach ($grades as $grade)
                            <option
                                value="{{ $grade->value }}"
                                @selected(
                                    request('grade') === $grade->value
                                )
                            >
                                {{ $grade->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="affiliation"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        所属
                    </label>

                    <input
                        id="affiliation"
                        name="affiliation"
                        type="text"
                        value="{{ request('affiliation') }}"
                        placeholder="所属名"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                </div>

                <div>
                    <label
                        for="class_group_id"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        クラス
                    </label>

                    <select
                        id="class_group_id"
                        name="class_group_id"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>

                        @foreach ($classGroups as $classGroup)
                            <option
                                value="{{ $classGroup->id }}"
                                @selected(
                                    (string) request('class_group_id')
                                    === (string) $classGroup->id
                                )
                            >
                                {{ $classGroup->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="status"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        在籍状態
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
                                @selected(request('status') === $status->value)
                            >
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="rounded-lg px-5 py-2 font-semibold lms-button-primary"
                    >
                        検索
                    </button>

                    <a
                        href="{{ route('admin.students.index') }}"
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
                <table
                    class="admin-students-table min-w-full divide-y lms-divide-neutral-subtle lms-table
                        lms-table--balanced"
                >
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--medium">
                                生徒番号
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                氏名
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                メールアドレス
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--compact">
                                学年
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                所属
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                提携校
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                クラス
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold lms-table-col--status">
                                在籍状態
                            </th>

                            <th class="px-5 py-3 text-right text-sm font-semibold lms-table-col--action">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-faint">
                        @forelse ($students as $student)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->student_no ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 font-medium">
                                    {{ $student->student_name }}
                                </td>

                                <td class="admin-students-table__email px-5 py-4 text-sm">
                                    {{ $student->user->email }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->grade->label() }}
                                </td>

                                <td class="admin-students-table__wrap px-5 py-4 text-sm">
                                    {{ $student->affiliation ?? '—' }}
                                </td>

                                <td class="admin-students-table__wrap px-5 py-4 text-sm">
                                    {{ $student->partner_school ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->classGroup->class_name }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
                                            'px-3 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success' =>
                                                $student->status === \App\Enums\StudentStatus::Active,
                                            'lms-bg-warning-muted lms-text-warning' =>
                                                $student->status === \App\Enums\StudentStatus::Suspended,
                                            'lms-bg-info-muted lms-text-info' =>
                                                $student->status === \App\Enums\StudentStatus::Graduated,
                                            'lms-bg-withdrawn lms-text-withdrawn' =>
                                                $student->status === \App\Enums\StudentStatus::Withdrawn,
                                        ])
                                    >
                                        {{ $student->status->label() }}
                                    </span>
                                </td>

                                <td
                                    class="admin-students-table__actions whitespace-nowrap px-5 py-4
                                        text-right text-sm"
                                >
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a
                                            href="{{ route(
                                                'admin.students.show',
                                                $student,
                                            ) }}"
                                            class="font-semibold lms-link-primary"
                                        >
                                            詳細
                                        </a>

                                        <a
                                            href="{{ route(
                                                'admin.students.edit',
                                                $student,
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
                                    colspan="9"
                                    class="px-6 py-12 text-center lms-text-neutral-muted"
                                >
                                    条件に一致する生徒はいません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $students->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
