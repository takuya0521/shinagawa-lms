@extends('layouts.guest')

@section('title', 'ログイン')
@section('page-style', 'resources/css/pages/auth/login.css')
@section('page-class', 'auth-page')

@section('content')
    <main class="auth-shell">
        {{-- LMSとして必要な学校識別情報だけを表示し、装飾的なキャッチコピーは配置しない。 --}}
        <section class="auth-identity" aria-label="品川高等学院">
            <div class="auth-identity__accent auth-identity__accent--pink" aria-hidden="true"></div>
            <div class="auth-identity__accent auth-identity__accent--aqua" aria-hidden="true"></div>

            <div class="auth-identity__content">
                <img
                    class="auth-identity__logo"
                    src="{{ asset('images/auth/logo.png') }}"
                    alt="品川高等学院 SHINAGAWA INTERNATIONAL HIGHSCHOOL"
                    width="420"
                    height="408"
                >
                <p class="auth-identity__system-name">Learning Management System</p>
            </div>
        </section>

        <section class="auth-form-area" aria-labelledby="login-heading">
            <div class="auth-card">
                <header class="auth-card__header">
                    <p class="auth-card__school-name">品川高等学院 LMS</p>
                    <h1 id="login-heading" class="auth-card__title">ログイン</h1>
                    <p class="auth-card__description">メールアドレスとパスワードを入力してください。</p>
                </header>

                <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
                    @csrf

                    <div class="auth-field">
                        <label for="email" class="auth-field__label">メールアドレス</label>
                        <div class="auth-input-wrap">
                            <svg class="auth-input-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M4 6.75h16v10.5H4z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="m4.75 7.5 7.25 5.25 7.25-5.25"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <input
                                id="email"
                                class="auth-input lms-form-control"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="example@shinagawahs.jp"
                                autocomplete="username"
                                inputmode="email"
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                required
                                autofocus
                            >
                        </div>
                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password" class="auth-field__label">パスワード</label>
                        <div class="auth-input-wrap">
                            <svg class="auth-input-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <rect
                                    x="5"
                                    y="10"
                                    width="14"
                                    height="10"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="M8.5 10V7.5a3.5 3.5 0 0 1 7 0V10"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <input
                                id="password"
                                class="auth-input lms-form-control"
                                name="password"
                                type="password"
                                placeholder="パスワードを入力"
                                autocomplete="current-password"
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                required
                            >
                            <button
                                type="button"
                                class="auth-password-toggle"
                                data-password-toggle
                                aria-controls="password"
                                aria-label="パスワードを表示する"
                                aria-pressed="false"
                            >
                                <svg
                                    class="auth-password-toggle__icon auth-password-toggle__show"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <path
                                        d="M2.75 12s3.25-5.25 9.25-5.25S21.25 12 21.25 12 18 17.25 12 17.25 2.75 12
                                            2.75 12Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/>
                                </svg>
                                <svg
                                    class="auth-password-toggle__icon auth-password-toggle__hide"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <path
                                        d="m4 4 16 16"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M10.25 6.93A8.7 8.7 0 0 1 12 6.75c6 0 9.25 5.25 9.25 5.25a15.8 15.8 0 0
                                            1-2.1 2.72M6.3 8.16C3.94 9.66 2.75 12 2.75 12S6 17.25 12 17.25c1.12 0
                                            2.13-.18 3.02-.48"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M9.7 9.7a3.25 3.25 0 0 0 4.6 4.6"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="auth-remember">
                        <input
                            class="auth-remember__input"
                            name="remember"
                            type="checkbox"
                            value="1"
                            @checked(old('remember'))
                        >
                        <span>ログイン状態を保持する</span>
                    </label>

                    <button type="submit" class="auth-submit">
                        <span>ログイン</span>
                        <svg class="auth-submit__icon" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M5 12h14M14 7l5 5-5 5"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </form>

                <p class="auth-help">パスワードを忘れた場合は、学校管理者へお問い合わせください。</p>
            </div>
        </section>
    </main>
@endsection
