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
    $pageScript = trim($__env->yieldContent('page-script'));
    $viteEntries = [
        'resources/css/app.css',
        'resources/css/layouts/authenticated-bundle.css',
        'resources/js/app.js',
    ];

    if ($pageStyle !== '') {
        $viteEntries[] = $pageStyle;
    }

    if ($pageScript !== '') {
        $viteEntries[] = $pageScript;
    }
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title') | 品川高等学院 LMS</title>
    @vite($viteEntries)
    @stack('head')
</head>
<body class="lms-body lms-role-{{ $roleKey }} {{ $pageClass }}">
    <div class="lms-shell" data-app-shell>
        {{-- モバイル表示では、背景領域の操作でもサイドバーを閉じられるようにする。 --}}
        <button class="lms-sidebar-overlay" type="button" data-sidebar-close aria-label="メニューを閉じる"></button>

        <aside class="lms-sidebar" id="lms-sidebar" aria-label="メインメニュー">
            <a href="{{ route($homeRoute) }}" class="lms-brand">
                <span class="lms-brand__logo-frame" aria-hidden="true">
                    <img
                        class="lms-brand__logo"
                        src="{{ asset('images/auth/logo.png') }}"
                        alt=""
                        width="420"
                        height="408"
                    >
                </span>
                <span class="lms-brand__text">
                    <small>品川高等学院 LMS</small>
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
                                @php($isActive = request()->routeIs($item['active']))
                                <a
                                    href="{{ route($item['route']) }}"
                                    @class(['lms-nav-link', 'is-active' => $isActive])
                                    @if ($isActive)
                                        aria-current="page"
                                    @endif
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
                            <x-nav-icon name="logout" />
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

                    <div class="lms-topbar__heading">
                        <h1>@yield('header-title')</h1>
                    </div>
                </div>
            </header>

            <main class="lms-content">
                @if (session()->has('status'))
                    <x-feedback type="success" :message="session('status')" />
                @endif
                @if (session()->has('success'))
                    <x-feedback type="success" :message="session('success')" />
                @endif
                @if (session()->has('error'))
                    <x-feedback type="error" :message="session('error')" />
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
