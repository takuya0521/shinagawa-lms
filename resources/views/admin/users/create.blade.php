@extends('layouts.app')

@section('title', 'ユーザー登録')
@section('header-title', 'ユーザー管理')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <header>
            <p class="text-sm font-semibold text-slate-500">
                ADMINISTRATION
            </p>

            <h1 class="mt-1 text-2xl font-bold">
                ユーザー登録
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                管理者、教員または生徒のアカウントを登録します。
            </p>
        </header>

        <section class="rounded-2xl bg-white p-8 shadow-sm">
            <form
                method="POST"
                action="{{ route('admin.users.store') }}"
                class="space-y-8"
            >
                @csrf

                @include('admin.users._form')

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="rounded-lg border border-slate-300 px-5 py-2 font-semibold hover:bg-slate-50"
                    >
                        キャンセル
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-5 py-2 font-semibold text-white hover:bg-slate-700"
                    >
                        登録
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection