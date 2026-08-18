@extends('layouts.app')

@section('page-class', 'page-pattern-form page-account-password-edit')

@section('title', 'パスワード変更')
@section('header-title', 'パスワード変更')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <section class="p-6 lms-panel">

            <form
                method="POST"
                action="{{ route('account.password.update') }}"
                class="space-y-5"
            >
                @csrf
                @method('PUT')

                <div>
                    <label
                        for="current_password"
                        class="block text-sm font-semibold lms-text-neutral-secondary"
                    >
                        現在のパスワード
                    </label>

                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        required
                    >

                    @error('current_password')
                        <p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lms-password-fields">
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            新しいパスワード
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                            required
                        >

                        <p class="mt-2 text-sm lms-text-neutral-muted">
                            8文字以上で、大文字・小文字・数字を含めてください。
                        </p>

                        @error('password')
                            <p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            確認用パスワード
                        </label>

                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                            required
                        >
                    </div>
                </div>

                <div class="flex justify-center border-t lms-border-neutral-subtle pt-5">
                    <button
                        type="submit"
                        class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                    >
                        変更する
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
