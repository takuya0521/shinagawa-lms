@php
    $loginUser = auth()->user();
    $roleKey = $loginUser->role->value;
    $roleLabel = $loginUser->role->label();
    $navigation = config("lms_navigation.roles.{$roleKey}");
    $roleWorkspace = $navigation['workspace'];
    $homeRoute = $navigation['home_route'];
    $menuSections = $navigation['sections'];
    $pageStyle = trim($__env->yieldContent('page-style'));
    $pageClass = trim($__env->yieldContent('page-class'));
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title') | 品川高等学院 LMS</title>
    @vite([
        'resources/css/app.css',
        'resources/css/layouts/authenticated.css',
        'resources/css/components/utility-compatibility.css',
        'resources/css/components/feedback.css',
        'resources/js/app.js',
    ])
    @if ($pageStyle !== '')
        {{-- 変更影響を対象画面内に限定するため、各画面の専用CSSだけを追加で読み込む。 --}}
        @vite($pageStyle)
    @endif
    @stack('head')
</head>
<body class="lms-body lms-role-{{ $roleKey }} {{ $pageClass }}">
    <div class="lms-shell" data-app-shell>
        {{-- モバイル表示では、背景領域の操作でもサイドバーを閉じられるようにする。 --}}
        <button class="lms-sidebar-overlay" type="button" data-sidebar-close aria-label="メニューを閉じる"></button>

        <aside class="lms-sidebar" id="lms-sidebar" aria-label="メインメニュー">
            <a href="{{ route($homeRoute) }}" class="lms-brand">
                <span class="lms-brand__mark" aria-hidden="true">LMS</span>
                <span class="lms-brand__text">
                    <small>品川高等学院</small>
                    <strong>{{ $roleWorkspace }}</strong>
                </span>
            </a>

            {{-- ロール別メニューは config/lms_navigation.php で一元管理する。 --}}
            <nav class="lms-nav">
                @foreach ($menuSections as $section)
                    <section class="lms-nav-section">
                        <p class="lms-nav-section__label">{{ $section['label'] }}</p>
                        <div class="lms-nav-section__items">
                            @foreach ($section['items'] as $item)
                                <a
                                    href="{{ route($item['route']) }}"
                                    @class(['lms-nav-link', 'is-active' => request()->routeIs($item['active'])])
                                    @if (request()->routeIs($item['active'])) aria-current="page" @endif
                                >
                                    <x-nav-icon :name="$item['icon']" />
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </nav>

            <div class="lms-sidebar__footer">
                <div class="lms-sidebar-user">
                    <span class="lms-sidebar-user__avatar" aria-hidden="true">
                        {{ \Illuminate\Support\Str::substr($loginUser->name, 0, 1) }}
                    </span>
                    <span class="lms-sidebar-user__body">
                        <strong>{{ $loginUser->name }}</strong>
                        <small>{{ $roleLabel }}</small>
                    </span>
                </div>

                <div class="lms-sidebar-actions">
                    <a
                        href="{{ route('account.password.edit') }}"
                        @class(['is-active' => request()->routeIs('account.password.*')])
                    >
                        <x-nav-icon name="key" />
                        <span>パスワード変更</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">
                            <span class="lms-logout-icon" aria-hidden="true">↗</span>
                            <span>ログアウト</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="lms-main">
            <header class="lms-topbar">
                <div class="lms-topbar__title-group">
                    <button
                        class="lms-menu-button"
                        type="button"
                        aria-controls="lms-sidebar"
                        aria-expanded="false"
                        data-sidebar-open
                    >
                        <span></span><span></span><span></span>
                        <span class="sr-only">メニューを開く</span>
                    </button>

                    <div>
                        <p class="lms-page-kicker">@yield('page-kicker', $roleWorkspace)</p>
                        <h1>@yield('header-title')</h1>
                        @hasSection('header-description')
                            <p class="lms-page-description">@yield('header-description')</p>
                        @endif
                    </div>
                </div>

                <div class="lms-user-chip" aria-label="ログインユーザー">
                    <span class="lms-user-chip__avatar" aria-hidden="true">
                        {{ \Illuminate\Support\Str::substr($loginUser->name, 0, 1) }}
                    </span>
                    <span>
                        <strong>{{ $loginUser->name }}</strong>
                        <small>{{ $roleLabel }}</small>
                    </span>
                </div>
            </header>

            <main class="lms-content">
                {{-- 処理結果は画面ごとに実装せず、共通レイアウトの同じ位置へ表示する。 --}}
                <x-feedback type="success" :message="session('status')" />
                <x-feedback type="success" :message="session('success')" />
                <x-feedback type="error" :message="session('error')" />

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
