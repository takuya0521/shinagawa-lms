@php
    $pageStyle = trim($__env->yieldContent('page-style'));
    $pageClass = trim($__env->yieldContent('page-class'));
    $viteEntries = [
        'resources/css/app.css',
        'resources/js/app.js',
    ];

    if ($pageStyle !== '') {
        $viteEntries[] = $pageStyle;
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
<body class="guest-body {{ $pageClass }}">
    @yield('content')
</body>
</html>
