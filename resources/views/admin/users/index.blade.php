@extends('layouts.app')

@section('title', 'ユーザー管理')
@section('header-title', 'ユーザー管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    ADMINISTRATION
                </p>

                <h1 class="mt-1 text-2xl font-bold">
                    ユーザー一覧
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    管理者、教員、生徒のアカウントを確認します。
                </p>
            </div>

            <p class="text-sm text-slate-500">
                {{ number_format($users->total()) }}件
            </p>
        </header>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.users.index') }}"
                class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_180px_180px_auto]"
            >
                <div>
                    <label
                        for="keyword"
                        class="block text-sm font-medium text-slate-700"
                    >
                        氏名・メールアドレス
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
                        for="role"
                        class="block text-sm font-medium text-slate-700"
                    >
                        ロール
                    </label>

                    <select
                        id="role"
                        name="role"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                        <option value="">すべて</option>

                        @foreach ($roles as $role)
                            <option
                                value="{{ $role->value }}"
                                @selected(request('role') === $role->value)
                            >
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="status"
                        class="block text-sm font-medium text-slate-700"
                    >
                        利用状態
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
                        href="{{ route('admin.users.index') }}"
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
                            <th class="px-6 py-3 text-left text-sm font-semibold">
                                ID
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold">
                                氏名
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold">
                                メールアドレス
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold">
                                ロール
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold">
                                利用状態
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ $user->id }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 font-medium">
                                    {{ $user->name }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    {{ $user->email }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    {{ $user->role->label() }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                            {{ $user->status === \App\Enums\UserStatus::Active
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-slate-200 text-slate-600' }}"
                                    >
                                        {{ $user->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-slate-500"
                                >
                                    条件に一致するユーザーはいません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection