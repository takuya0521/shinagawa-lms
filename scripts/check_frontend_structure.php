<?php

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$viewRoot = $projectRoot.'/resources/views';
$cssRoot = $projectRoot.'/resources/css';
$errors = [];
$pageStyleReferences = [];

/**
 * 指定ディレクトリ配下から、拡張子に一致するファイルを取得する。
 *
 * @return list<string>
 */
function collectFiles(string $directory, string $suffix): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), $suffix)) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

foreach (collectFiles($viewRoot, '.blade.php') as $viewPath) {
    $contents = file_get_contents($viewPath);

    if ($contents === false) {
        $errors[] = "Bladeを読み込めません: {$viewPath}";

        continue;
    }

    // CSSの影響範囲を追跡できなくなるため、Blade内のインラインstyleを禁止する。
    if (preg_match('/\sstyle\s*=/', $contents) === 1) {
        $errors[] = "インラインstyleが残っています: {$viewPath}";
    }

    $requiresPageStyle = str_contains($contents, "@extends('layouts.app')")
        || preg_match('#/resources/views/errors/\d{3}\.blade\.php$#', str_replace('\\', '/', $viewPath)) === 1;

    if (! $requiresPageStyle) {
        continue;
    }

    if (preg_match("/@section\('page-style', '([^']+)'\)/", $contents, $styleMatch) !== 1) {
        $errors[] = "page-styleが設定されていません: {$viewPath}";

        continue;
    }

    if (preg_match("/@section\('page-class', '([^']+)'\)/", $contents) !== 1) {
        $errors[] = "page-classが設定されていません: {$viewPath}";
    }

    $cssPath = $projectRoot.'/'.$styleMatch[1];
    $pageStyleReferences[] = $cssPath;

    if (! is_file($cssPath)) {
        $errors[] = "ページCSSが存在しません: {$viewPath} -> {$cssPath}";
    }
}

foreach (collectFiles($cssRoot, '.css') as $cssPath) {
    $contents = file_get_contents($cssPath);

    if ($contents === false) {
        $errors[] = "CSSを読み込めません: {$cssPath}";

        continue;
    }

    if (str_contains($contents, '!important')) {
        $errors[] = "!importantが含まれています: {$cssPath}";
    }

    if (preg_match('/\/\*\s*(微調整|最終修正|FINAL FIX|強制上書き)/u', $contents) === 1) {
        $errors[] = "禁止コメントが含まれています: {$cssPath}";
    }

    preg_match_all('/@import\s+[\'\"]([^\'\"]+)[\'\"]/', $contents, $imports);
    foreach ($imports[1] as $importPath) {
        if ($importPath === 'tailwindcss') {
            continue;
        }

        $resolvedPath = realpath(dirname($cssPath).'/'.$importPath);
        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            $errors[] = "CSSのimport先が存在しません: {$cssPath} -> {$importPath}";
        }
    }
}

// メニュー設定とルートが分離されているため、設定変更時のルート名誤りを機械的に検出する。
$app = require $projectRoot.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$routeNames = collect($app['router']->getRoutes()->getRoutes())
    ->map(static fn ($route): ?string => $route->getName())
    ->filter()
    ->flip();

foreach (config('lms_navigation.roles', []) as $roleKey => $navigation) {
    $configuredRoutes = [$navigation['home_route']];

    foreach ($navigation['sections'] as $section) {
        foreach ($section['items'] as $item) {
            $configuredRoutes[] = $item['route'];
        }
    }

    foreach ($configuredRoutes as $routeName) {
        if (! $routeNames->has($routeName)) {
            $errors[] = "ナビゲーションのルートが存在しません: {$roleKey} -> {$routeName}";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        'フロントエンド構成チェック完了: ページCSS参照 %d件 / CSS %d件%s',
        count($pageStyleReferences),
        count(collectFiles($cssRoot, '.css')),
        PHP_EOL,
    ),
);
