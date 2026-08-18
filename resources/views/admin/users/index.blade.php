@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-users-index')

@section('title', 'ユーザー管理')
@section('header-title', 'ユーザー管理')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <div class="flex items-center gap-4">
                <p class="text-sm lms-text-neutral-muted">
                    {{ number_format($users->total()) }}件
                </p>

                <a
                    href="{{ route('admin.users.create') }}"
                    class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                >
                    ユーザー登録
                </a>
            </div>
        </header>

        <section class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.users.index') }}"
                class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_180px_180px_auto]"
            >
                <div>
                    <label
                        for="keyword"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        氏名・メールアドレス
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
                        for="role"
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        ロール
                    </label>

                    <select
                        id="role"
                        name="role"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
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
                        class="block text-sm font-medium lms-text-neutral-secondary"
                    >
                        利用状態
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
                        href="{{ route('admin.users.index') }}"
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
                            <th class="px-6 py-3 text-left text-sm font-semibold lms-table-col--compact">
                                ID
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold">
                                氏名
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold">
                                メールアドレス
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold lms-table-col--medium">
                                ロール
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold lms-table-col--status">
                                利用状態
                            </th>

                            <th class="px-6 py-3 text-right text-sm font-semibold lms-table-col--action">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-faint">
                        @forelse ($users as $user)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm lms-text-neutral-muted">
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

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <span
                                        class="inline-flex whitespace-nowrap rounded-full px-3 py-1
                                                text-xs font-semibold {{
                                            $user->status === \App\Enums\UserStatus::Active ? 'lms-bg-success-muted
                                            lms-text-success' : 'lms-bg-neutral-disabled lms-text-neutral-subtle' }}"
                                    >
                                        {{ $user->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        <a
                                            href="{{ route('admin.users.edit', $user) }}"
                                            class="font-semibold lms-link-primary"
                                        >
                                            編集
                                        </a>

                                        @if ($user->is(auth()->user()))
                                            <span class="text-sm lms-text-neutral-disabled">
                                                ログイン中
                                            </span>
                                        @else
                                            <form
                                                method="POST"
                                                action="{{ route('admin.users.status.update', $user) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <input class="lms-form-control"
                                                    type="hidden"
                                                    name="status"
                                                    value="{{ $user->status === \App\Enums\UserStatus::Active
                                                        ? \App\Enums\UserStatus::Suspended->value
                                                        : \App\Enums\UserStatus::Active->value }}"
                                                >

                                                <button
                                                    type="submit"
                                                    class="text-sm font-semibold {{ $user->status ===
                                                        \App\Enums\UserStatus::Active ? 'lms-text-danger-strong' :
                                                        'lms-text-success' }}"
                                                >
                                                    {{ $user->status === \App\Enums\UserStatus::Active
                                                        ? '利用停止'
                                                        : '利用再開' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center lms-text-neutral-muted"
                                >
                                    条件に一致するユーザーはいません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
