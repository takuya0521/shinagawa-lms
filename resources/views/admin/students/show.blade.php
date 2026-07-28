@extends('layouts.app')

@section('title', '生徒詳細')
@section('header-title', '生徒詳細')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    STUDENT DETAIL
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    生徒詳細
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    生徒の基本情報とログインアカウントを確認します。
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        'admin.users.edit',
                        $student->user,
                    ) }}"
                    class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    編集する
                </a>

                <a
                    href="{{ route('admin.students.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
                >
                    一覧へ戻る
                </a>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500">
                            {{ $student->student_no ?? '生徒番号未設定' }}
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-900">
                            {{ $student->student_name }}
                        </h2>
                    </div>

                    <span
                        @class([
                            'inline-flex rounded-full px-4 py-2 text-sm font-semibold',
                            'bg-emerald-100 text-emerald-700' => $student->status === \App\Enums\StudentStatus::Active,
                            'bg-amber-100 text-amber-700' => $student->status === \App\Enums\StudentStatus::Suspended,
                            'bg-blue-100 text-blue-700' => $student->status === \App\Enums\StudentStatus::Graduated,
                            'bg-rose-100 text-rose-700' => $student->status === \App\Enums\StudentStatus::Withdrawn,
                        ])
                    >
                        {{ $student->status->label() }}
                    </span>
                </div>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        生徒番号
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->student_no ?? '未設定' }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        生徒氏名
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->student_name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        学年
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->grade }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        クラス
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->classGroup->class_name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        所属
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->affiliation ?? '未設定' }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        提携校
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->partner_school ?? '未設定' }}
                    </dd>
                </div>

                <div class="px-6 py-5 md:col-span-2">
                    <dt class="text-sm font-medium text-slate-500">
                        在籍状態
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->status->label() }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">
                    ログインアカウント
                </h2>

                <p class="mt-1 text-sm text-slate-600">
                    LMSへログインするためのアカウント情報です。
                </p>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        アカウント氏名
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->user->name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        メールアドレス
                    </dt>

                    <dd class="mt-2 break-all text-slate-900">
                        {{ $student->user->email }}
                    </dd>
                </div>

                <div class="px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        ロール
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->user->role->label() }}
                    </dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        利用状態
                    </dt>

                    <dd class="mt-2">
                        <span
                            @class([
                                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                'bg-emerald-100 text-emerald-700' => $student->user->status === \App\Enums\UserStatus::Active,
                                'bg-slate-200 text-slate-700' => $student->user->status === \App\Enums\UserStatus::Suspended,
                            ])
                        >
                            {{ $student->user->status->label() }}
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
                        生徒ID
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $student->id }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        登録日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $student->created_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        更新日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $student->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
@endsection