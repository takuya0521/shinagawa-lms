@extends('layouts.app')

@section('title', 'ユーザー編集')
@section('header-title', 'ユーザー編集')

@section('content')
    <div class="mx-auto max-w-4xl">
        <header>
            <p class="text-sm font-semibold text-slate-500">
                USER EDIT
            </p>

            <h1 class="mt-1 text-2xl font-bold">
                ユーザー編集
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                アカウント情報と関連情報を更新します。
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('admin.users.update', $user) }}"
            class="mt-6 rounded-2xl bg-white p-6 shadow-sm"
        >
            @csrf
            @method('PUT')

            @include('admin.users._form', [
                'submitLabel' => '更新する',
            ])
        </form>
    </div>
@endsection