@php
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
    @vite(['resources/css/app.css', $pageStyle, 'resources/js/app.js'])
</head>
<body class="{{ $pageClass }}">
    <main class="error-page">
        <section class="error-card">
            <div class="error-card__mark" aria-hidden="true">LMS</div>
            <p class="error-card__code">@yield('code')</p>
            <h1>@yield('heading')</h1>
            <p class="error-card__message">@yield('message')</p>

            {{-- 復旧方法を限定せず、トップへの移動と直前画面への復帰を選べるようにする。 --}}
            <div class="error-card__actions">
                <a href="{{ url('/') }}">トップへ戻る</a>
                <button type="button" data-history-back>前の画面へ戻る</button>
            </div>
        </section>
    </main>
</body>
</html>
