<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>ダッシュボード | 品川高等学院 LMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    品川高等学院 LMS
                </p>

                <h1 class="text-xl font-bold">
                    ダッシュボード
                </h1>
            </div>

            <form method="POST" action="/logout">
                @csrf

                <button
                    type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-100"
                >
                    ログアウト
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-10">
        <section class="rounded-2xl bg-white p-8 shadow-sm">
            <p class="text-sm text-slate-500">
                ログインユーザー
            </p>

            <h2 class="mt-2 text-2xl font-bold">
                {{ auth()->user()->name }}
            </h2>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">
                        ロール
                    </dt>

                    <dd class="mt-1 font-semibold">
                        {{ auth()->user()->role->label() }}
                    </dd>
                </div>

                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">
                        利用状態
                    </dt>

                    <dd class="mt-1 font-semibold">
                        {{ auth()->user()->status->label() }}
                    </dd>
                </div>
            </dl>
        </section>
    </main>
</body>
</html>