@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/students/index.css')
@section('page-class', 'page-pattern-list page-admin-students-index')

@section('title', '生徒管理')
@section('header-title', '生徒管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    STUDENT MANAGEMENT
                </p>

                <h1 class="mt-1 text-2xl font-bold">
                    生徒一覧
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    生徒番号、所属クラス、学年、在籍状態を確認します。
                </p>
            </div>

            <div class="flex items-center gap-4">
                <p class="text-sm text-slate-500">
                    {{ number_format($students->total()) }}件
                </p>

                <a
                    href="{{ route('admin.students.create') }}"
                    class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    生徒登録
                </a>
            </div>
        </header>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.students.index') }}"
                class="grid gap-4 lg:grid-cols-3"
            >
                <div>
                    <label
                        for="keyword"
                        class="block text-sm font-medium text-slate-700"
                    >
                        生徒番号・氏名・メール・提携校
                    </label>

                    <input
                        id="keyword"
                        name="keyword"
                        type="search"
                        value="{{ request('keyword') }}"
                        placeholder="検索キーワード"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                </div>

                <div>
                    <label
                        for="grade"
                        class="block text-sm font-medium text-slate-700"
                    >
                        学年
                    </label>

                    <select
                        id="grade"
                        name="grade"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        class="block text-sm font-medium text-slate-700"
                    >
                        所属
                    </label>

                    <input
                        id="affiliation"
                        name="affiliation"
                        type="text"
                        value="{{ request('affiliation') }}"
                        placeholder="所属名"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                </div>

                <div>
                    <label
                        for="class_group_id"
                        class="block text-sm font-medium text-slate-700"
                    >
                        クラス
                    </label>

                    <select
                        id="class_group_id"
                        name="class_group_id"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        class="block text-sm font-medium text-slate-700"
                    >
                        在籍状態
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        class="rounded-lg bg-slate-900 px-5 py-2 font-semibold text-white hover:bg-slate-700"
                    >
                        検索
                    </button>

                    <a
                        href="{{ route('admin.students.index') }}"
                        class="rounded-lg border border-slate-300 px-5 py-2 font-semibold hover:bg-slate-50"
                    >
                        クリア
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                生徒番号
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                氏名
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                メールアドレス
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
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

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                在籍状態
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $student)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->student_no ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 font-medium">
                                    {{ $student->student_name }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->user->email }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->grade->label() }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->affiliation ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->partner_school ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $student->classGroup->class_name }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-700' => $student->status === \App\Enums\StudentStatus::Active,
                                            'bg-amber-100 text-amber-700' => $student->status === \App\Enums\StudentStatus::Suspended,
                                            'bg-blue-100 text-blue-700' => $student->status === \App\Enums\StudentStatus::Graduated,
                                            'bg-rose-100 text-rose-700' => $student->status === \App\Enums\StudentStatus::Withdrawn,
                                        ])
                                    >
                                        {{ $student->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <div class="flex items-center gap-3">
                                        <a
                                            href="{{ route(
                                                'admin.students.show',
                                                $student,
                                            ) }}"
                                            class="font-semibold text-slate-700 underline underline-offset-4 hover:text-slate-950"
                                        >
                                            詳細
                                        </a>

                                        <a
                                            href="{{ route(
                                                'admin.students.edit',
                                                $student,
                                            ) }}"
                                            class="font-semibold text-slate-700 underline underline-offset-4 hover:text-slate-950"
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
                                    class="px-6 py-12 text-center text-slate-500"
                                >
                                    条件に一致する生徒はいません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $students->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
