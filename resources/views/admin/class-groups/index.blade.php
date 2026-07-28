@extends('layouts.app')

@section('title', 'クラス管理')
@section('header-title', 'クラス管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    CLASS GROUP MANAGEMENT
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    クラス一覧
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    午前・午後などの所属クラスを管理します。
                </p>
            </div>

            <div class="flex items-center gap-4">
                <p class="text-sm text-slate-500">
                    {{ number_format($classGroups->total()) }}件
                </p>

                <a
                    href="{{ route('admin.class-groups.create') }}"
                    class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    クラス登録
                </a>
            </div>
        </header>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.class-groups.index') }}"
                class="grid gap-4 md:grid-cols-3"
            >
                <div class="md:col-span-2">
                    <label
                        for="keyword"
                        class="block text-sm font-medium text-slate-700"
                    >
                        コード・クラス名・説明
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
                        for="status"
                        class="block text-sm font-medium text-slate-700"
                    >
                        状態
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
                        class="rounded-lg bg-slate-900 px-5 py-2 font-semibold text-white hover:bg-slate-700"
                    >
                        検索
                    </button>

                    <a
                        href="{{ route('admin.class-groups.index') }}"
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
                                コード
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                クラス名
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                説明
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                所属生徒数
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                状態
                            </th>

                            <th class="px-5 py-3 text-left text-sm font-semibold">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($classGroups as $classGroup)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 font-mono text-sm">
                                    {{ $classGroup->class_code }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 font-medium">
                                    {{ $classGroup->class_name }}
                                </td>

                                <td class="max-w-md px-5 py-4 text-sm text-slate-600">
                                    {{ $classGroup->description ?? '未設定' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ number_format($classGroup->students_count) }}名
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-700' => $classGroup->status === \App\Enums\MasterStatus::Active,
                                            'bg-slate-200 text-slate-700' => $classGroup->status === \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $classGroup->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <a
                                        href="{{ route(
                                            'admin.class-groups.edit',
                                            $classGroup,
                                        ) }}"
                                        class="font-semibold text-slate-700 underline underline-offset-4 hover:text-slate-950"
                                    >
                                        編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center text-slate-500"
                                >
                                    条件に一致するクラスはありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($classGroups->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $classGroups->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection