@extends('layouts.app')

@section('title', 'ユーザー登録')
@section('header-title', 'ユーザー登録')

@section('content')
    <div class="mx-auto max-w-4xl">
        <header>
            <p class="text-sm font-semibold text-slate-500">
                USER REGISTRATION
            </p>

            <h1 class="mt-1 text-2xl font-bold">
                ユーザー登録
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                ログインアカウントと必要な関連情報を登録します。
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('admin.users.store') }}"
            class="mt-6 rounded-2xl bg-white p-6 shadow-sm"
        >
            @csrf

            @include('admin.users._form', [
                'submitLabel' => '登録する',
            ])
        </form>
    </div>
@endsection