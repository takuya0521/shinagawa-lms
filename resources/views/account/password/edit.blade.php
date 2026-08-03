@extends('layouts.app')

@section('page-style', 'resources/css/pages/account/password/edit.css')
@section('page-class', 'page-pattern-form page-account-password-edit')

@section('title', 'パスワード変更')
@section('header-title', 'パスワード変更')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">C-002（ローカル認証版）</p>
            <h1 class="mt-1 text-2xl font-bold">パスワード変更</h1>
            <p class="mt-2 text-sm text-slate-600">現在のパスワードを確認し、新しいパスワードへ変更します。</p>

            <form method="POST" action="{{ route('account.password.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-semibold text-slate-700">現在のパスワード</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    @error('current_password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">新しいパスワード</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    <p class="mt-2 text-sm text-slate-500">8文字以上で、大文字・小文字・数字を含めてください。</p>
                    @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">確認用パスワード</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700">変更する</button>
                </div>
            </form>
        </section>
    </div>
@endsection
