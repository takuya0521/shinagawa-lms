@extends('layouts.guest')

@section('title', 'ログイン')
@section('page-style', 'resources/css/pages/auth/login.css')
@section('page-class', 'auth-page')

@section('content')
    <main class="auth-shell">
        {{-- 学校名とLMSの用途を先に示し、利用者が正しいシステムへアクセスしたことを確認できるようにする。 --}}
        <section class="auth-visual" aria-label="品川高等学院 LMS">
            <div class="auth-brand">
                <span class="auth-brand__mark" aria-hidden="true">LMS</span>
                <span>
                    <small>SHINAGAWA HIGH SCHOOL</small>
                    <strong>品川高等学院 LMS</strong>
                </span>
            </div>

            <div class="auth-message">
                <p>Learning Management System</p>
                <h1>学校生活に必要な情報を、ひとつの場所に。</h1>
                <small>授業、出欠、評価、面談、お知らせ、Googleサービスを、権限に応じて安全に利用できます。</small>
            </div>
        </section>

        <section class="auth-form-area">
            <div class="auth-card">
                <header class="auth-card__header">
                    <p>Sign in</p>
                    <h2>ログイン</h2>
                    <small>登録されているメールアドレスとパスワードを入力してください。</small>
                </header>

                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf

                    <div class="auth-field">
                        <label for="email">メールアドレス</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            inputmode="email"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                            required
                            autofocus
                        >
                        @error('email')
                            <p id="email-error" class="auth-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password">パスワード</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                            required
                        >
                        @error('password')
                            <p id="password-error" class="auth-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="auth-remember">
                        <input name="remember" type="checkbox" value="1">
                        <span>ログイン状態を保持する</span>
                    </label>

                    <button type="submit" class="auth-submit">ログイン</button>
                </form>
            </div>
        </section>
    </main>
@endsection
