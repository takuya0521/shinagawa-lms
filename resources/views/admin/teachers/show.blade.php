@extends('layouts.app')

@section('title', '教員詳細')
@section('header-title', '教員詳細')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    TEACHER DETAIL
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    教員詳細
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    教員情報とログインアカウントを確認します。
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('admin.teachers.edit', $teacher) }}"
                    class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    編集する
                </a>

                <a
                    href="{{ route('admin.teachers.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
                >
                    一覧へ戻る
                </a>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ $teacher->user->name }}
                    </h2>

                    <span
                        @class([
                            'inline-flex rounded-full px-4 py-2 text-sm font-semibold',
                            'bg-emerald-100 text-emerald-700' => $teacher->status === \App\Enums\MasterStatus::Active,
                            'bg-slate-200 text-slate-700' => $teacher->status === \App\Enums\MasterStatus::Inactive,
                        ])
                    >
                        {{ $teacher->status->label() }}
                    </span>
                </div>
            </div>

            <dl>
                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        担当科目メモ
                    </dt>

                    <dd class="mt-2 whitespace-pre-wrap text-slate-900">{{ $teacher->subject_notes ?? '未設定' }}</dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        教員状態
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $teacher->status->label() }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">
                    ログインアカウント
                </h2>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        氏名
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $teacher->user->name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        メールアドレス
                    </dt>

                    <dd class="mt-2 break-all text-slate-900">
                        {{ $teacher->user->email }}
                    </dd>
                </div>

                <div class="px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        ロール
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $teacher->user->role->label() }}
                    </dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        アカウント利用状態
                    </dt>

                    <dd class="mt-2">
                        <span
                            @class([
                                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                'bg-emerald-100 text-emerald-700' => $teacher->user->status === \App\Enums\UserStatus::Active,
                                'bg-slate-200 text-slate-700' => $teacher->user->status === \App\Enums\UserStatus::Suspended,
                            ])
                        >
                            {{ $teacher->user->status->label() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">
                システム情報
            </h2>

            <dl class="mt-4 grid gap-5 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        教員ID
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $teacher->id }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        登録日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $teacher->created_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        更新日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $teacher->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
@endsection