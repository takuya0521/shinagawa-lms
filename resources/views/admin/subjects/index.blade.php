@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/subjects/index.css')
@section('page-class', 'page-pattern-list page-admin-subjects-index')

@section('title', '科目管理')
@section('header-title', '科目管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    科目管理
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    時間割で使用する科目の登録・編集・有効状態を管理します。
                </p>
            </div>

            <a
                href="{{ route('admin.subjects.create') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
            >
                科目を登録
            </a>
        </div>


        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.subjects.index') }}"
                class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto_auto]"
            >
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
                        value="{{ $keyword }}"
                        placeholder="科目コード・科目名"
                        class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                </div>

                <div>
                    <label
                        for="status"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        状態
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
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
                        class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700"
                    >
                        検索
                    </button>
                </div>

                <div class="flex items-end">
                    <a
                        href="{{ route('admin.subjects.index') }}"
                        class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        クリア
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                科目コード
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                科目名
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                状態
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($subjects as $subject)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">
                                    {{ $subject->subject_code }}
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ $subject->subject_name }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $subject->status === \App\Enums\MasterStatus::Active,
                                            'bg-slate-100 text-slate-700' => $subject->status === \App\Enums\MasterStatus::Inactive,
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
                                        class="font-semibold text-blue-700 hover:text-blue-900"
                                    >
                                        編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-6 py-12 text-center text-sm text-slate-500"
                                >
                                    条件に一致する科目はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($subjects->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $subjects->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
