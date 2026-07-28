<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>ログイン | 品川高等学院 LMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
            <header class="mb-8 text-center">
                <p class="text-sm font-semibold tracking-wider text-slate-500">
                    SHINAGAWA HIGH SCHOOL
                </p>

                <h1 class="mt-2 text-2xl font-bold">
                    品川高等学院 LMS
                </h1>

                <p class="mt-3 text-sm text-slate-600">
                    メールアドレスとパスワードを入力してください。
                </p>
            </header>

            <form method="POST" action="/login" class="space-y-6">
                @csrf

                <div>
                    <label
                        for="email"
                        class="block text-sm font-medium text-slate-700"
                    >
                        メールアドレス
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        required
                        autofocus
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-slate-700 focus:ring-2 focus:ring-slate-200"
                    >

                    @error('email')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-slate-700"
                    >
                        パスワード
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-slate-700 focus:ring-2 focus:ring-slate-200"
                    >

                    @error('password')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        name="remember"
                        type="checkbox"
                        value="1"
                        class="rounded border-slate-300"
                    >

                    ログイン状態を保持する
                </label>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    ログイン
                </button>
            </form>
        </section>
    </main>
</body>
</html>