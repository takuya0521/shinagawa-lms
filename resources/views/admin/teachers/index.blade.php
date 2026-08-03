@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/teachers/index.css')
@section('page-class', 'page-pattern-list page-admin-teachers-index')

@section('title', '教員管理')
@section('header-title', '教員管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    TEACHER MANAGEMENT
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    教員一覧
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    教員アカウント、担当科目メモ、利用状態を確認します。
                </p>
            </div>

            <div class="flex items-center gap-4">
                <p class="text-sm text-slate-500">
                    {{ number_format($teachers->total()) }}件
                </p>

                <a
                    href="{{ route('admin.teachers.create') }}"
                    class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    教員登録
                </a>
            </div>
        </header>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.teachers.index') }}"
                class="grid gap-4 lg:grid-cols-3"
            >
                <div>
                    <label
                        for="keyword"
                        class="block text-sm font-medium text-slate-700"
                    >
                        氏名・メール・担当科目メモ
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
                        for="teacher_status"
                        class="block text-sm font-medium text-slate-700"
                    >
                        教員状態
                    </label>

                    <select
                        id="teacher_status"
                        name="teacher_status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        class="block text-sm font-medium text-slate-700"
                    >
                        アカウント利用状態
                    </label>

                    <select
                        id="account_status"
                        name="account_status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        class="rounded-lg bg-slate-900 px-5 py-2 font-semibold text-white hover:bg-slate-700"
                    >
                        検索
                    </button>

                    <a
                        href="{{ route('admin.teachers.index') }}"
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

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                教員状態
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                利用状態
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                    {{ $teacher->id }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 font-medium">
                                    {{ $teacher->user->name }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $teacher->user->email }}
                                </td>

                                <td class="max-w-sm px-5 py-4 text-sm text-slate-600">
                                    {{ $teacher->subject_notes ?? '未設定' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-700' => $teacher->status === \App\Enums\MasterStatus::Active,
                                            'bg-slate-200 text-slate-700' => $teacher->status === \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $teacher->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-700' => $teacher->user->status === \App\Enums\UserStatus::Active,
                                            'bg-slate-200 text-slate-700' => $teacher->user->status === \App\Enums\UserStatus::Suspended,
                                        ])
                                    >
                                        {{ $teacher->user->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <div class="flex items-center gap-3">
                                        <a
                                            href="{{ route(
                                                'admin.teachers.show',
                                                $teacher,
                                            ) }}"
                                            class="font-semibold text-slate-700 underline underline-offset-4 hover:text-slate-950"
                                        >
                                            詳細
                                        </a>

                                        <a
                                            href="{{ route(
                                                'admin.teachers.edit',
                                                $teacher,
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
                                    colspan="7"
                                    class="px-6 py-12 text-center text-slate-500"
                                >
                                    条件に一致する教員はいません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($teachers->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $teachers->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
