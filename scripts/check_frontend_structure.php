<?php

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$viewRoot = $projectRoot.'/resources/views';
$cssRoot = $projectRoot.'/resources/css';
$errors = [];
$pageStyleReferences = [];
$selectorOrigins = [];
$cssImportGraph = [];

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

/**
 * CSSの空白差を除去し、同じセレクターを比較可能な文字列へ整形する。
 */
function normalizeSelector(string $selector): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $selector));
}

/**
 * Blade内のHTMLタグを確認し、重複属性やインラインイベントを検出する。
 *
 * @param  list<string>  $errors
 */
function inspectHtmlTags(string $contents, string $relativePath, array &$errors): void
{
    $tagPattern = "/<([A-Za-z][\\w:-]*)(?:(?:\"[^\"]*\"|'[^']*'|[^'\">])*)>/su";

    if (preg_match_all($tagPattern, $contents, $tagMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
        $errors[] = "HTMLタグを解析できません: {$relativePath}";

        return;
    }

    foreach ($tagMatches as $tagMatch) {
        $tag = $tagMatch[0][0];
        $tagName = strtolower($tagMatch[1][0]);
        $line = substr_count(substr($contents, 0, $tagMatch[0][1]), "\n") + 1;
        $attributeNames = [];

        preg_match_all(
            '/(?<![\\w:-])([:@A-Za-z_][\\w:.-]*)\\s*=/u',
            $tag,
            $attributeMatches,
        );

        foreach ($attributeMatches[1] as $attributeName) {
            $normalizedName = strtolower($attributeName);

            if (isset($attributeNames[$normalizedName])) {
                $errors[] = "同一HTML属性が重複しています: {$relativePath}:{$line} attribute={$normalizedName}";
            }

            $attributeNames[$normalizedName] = true;
        }

        if (preg_match('/\\son[a-z]+\\s*=/iu', $tag) === 1) {
            $errors[] = "インラインイベント属性が残っています: {$relativePath}:{$line}";
        }

        if ($tagName === 'button' && ! isset($attributeNames['type'])) {
            $errors[] = "button要素にtype属性がありません: {$relativePath}:{$line}";
        }
    }
}

/**
 * 文字列・丸括弧・角括弧を考慮しながら、次の区切り位置を取得する。
 */
function findCssDelimiter(string $contents, int $offset, array $delimiters): ?int
{
    $length = strlen($contents);
    $quote = null;
    $parenthesisDepth = 0;
    $bracketDepth = 0;

    for ($index = $offset; $index < $length; $index++) {
        $character = $contents[$index];

        if ($quote !== null) {
            if ($character === '\\') {
                $index++;

                continue;
            }

            if ($character === $quote) {
                $quote = null;
            }

            continue;
        }

        if ($character === '"' || $character === "'") {
            $quote = $character;

            continue;
        }

        if ($character === '(') {
            $parenthesisDepth++;

            continue;
        }

        if ($character === ')') {
            $parenthesisDepth--;

            continue;
        }

        if ($character === '[') {
            $bracketDepth++;

            continue;
        }

        if ($character === ']') {
            $bracketDepth--;

            continue;
        }

        if ($parenthesisDepth === 0
            && $bracketDepth === 0
            && in_array($character, $delimiters, true)) {
            return $index;
        }
    }

    return null;
}

/**
 * 開始波括弧に対応する終了波括弧の位置を取得する。
 */
function findMatchingBrace(string $contents, int $openingBrace): ?int
{
    $length = strlen($contents);
    $depth = 1;
    $quote = null;

    for ($index = $openingBrace + 1; $index < $length; $index++) {
        $character = $contents[$index];

        if ($quote !== null) {
            if ($character === '\\') {
                $index++;

                continue;
            }

            if ($character === $quote) {
                $quote = null;
            }

            continue;
        }

        if ($character === '"' || $character === "'") {
            $quote = $character;

            continue;
        }

        if ($character === '{') {
            $depth++;
        } elseif ($character === '}') {
            $depth--;

            if ($depth === 0) {
                return $index;
            }
        }
    }

    return null;
}

/**
 * 宣言ブロック内の同一プロパティ重複を検出する。
 *
 * @return list<string>
 */
function findDuplicateProperties(string $body): array
{
    $properties = [];
    $duplicates = [];
    $offset = 0;
    $length = strlen($body);

    while ($offset < $length) {
        $delimiter = findCssDelimiter($body, $offset, [';']);
        $end = $delimiter ?? $length;
        $declaration = trim(substr($body, $offset, $end - $offset));
        $offset = $delimiter === null ? $length : $delimiter + 1;

        if ($declaration === '' || ! str_contains($declaration, ':')) {
            continue;
        }

        [$property] = explode(':', $declaration, 2);
        $property = strtolower(trim($property));
        if ($property === '' || str_starts_with($property, '@')) {
            continue;
        }

        if (isset($properties[$property])) {
            $duplicates[] = $property;
        }

        $properties[$property] = true;
    }

    return array_values(array_unique($duplicates));
}

/**
 * CSSルールを再帰的に解析し、セレクターとプロパティの重複を検出する。
 *
 * @param  array<string, string>  $selectorOrigins
 * @param  list<string>  $errors
 */
function inspectCssRules(
    string $contents,
    string $relativePath,
    array &$selectorOrigins,
    array &$errors,
): void {
    $offset = 0;
    $length = strlen($contents);

    while ($offset < $length) {
        while ($offset < $length && ctype_space($contents[$offset])) {
            $offset++;
        }

        if ($offset >= $length) {
            break;
        }

        $delimiter = findCssDelimiter($contents, $offset, ['{', ';']);
        if ($delimiter === null) {
            break;
        }

        $header = trim(substr($contents, $offset, $delimiter - $offset));
        $delimiterCharacter = $contents[$delimiter];

        if ($delimiterCharacter === ';') {
            $offset = $delimiter + 1;

            continue;
        }

        $closingBrace = findMatchingBrace($contents, $delimiter);
        if ($closingBrace === null) {
            $errors[] = "CSSの波括弧が閉じられていません: {$relativePath}";

            return;
        }

        $body = substr($contents, $delimiter + 1, $closingBrace - $delimiter - 1);
        $offset = $closingBrace + 1;

        if (str_starts_with($header, '@')) {
            $atRule = strtolower(strtok($header, " \t\r\n"));
            if (in_array($atRule, ['@media', '@supports', '@container', '@layer', '@scope'], true)) {
                inspectCssRules($body, $relativePath, $selectorOrigins, $errors);
            }

            continue;
        }

        $selector = normalizeSelector($header);
        if ($selector === '') {
            continue;
        }

        if (isset($selectorOrigins[$selector])) {
            $errors[] = sprintf(
                '同一CSSセレクターが再定義されています: %s first=%s second=%s',
                $selector,
                $selectorOrigins[$selector],
                $relativePath,
            );
        } else {
            $selectorOrigins[$selector] = $relativePath;
        }

        foreach (findDuplicateProperties($body) as $property) {
            $errors[] = "同一CSSプロパティが重複しています: {$relativePath} selector={$selector} property={$property}";
        }
    }
}

foreach (collectFiles($viewRoot, '.blade.php') as $viewPath) {
    $contents = file_get_contents($viewPath);

    if ($contents === false) {
        $errors[] = "Bladeを読み込めません: {$viewPath}";

        continue;
    }

    $relativeViewPath = str_replace($projectRoot.'/', '', str_replace('\\', '/', $viewPath));

    if (preg_match('/(?<!\{)\{--.*?--\}/su', $contents) === 1) {
        $errors[] = "Bladeコメントの開始記号が不正です: {$relativeViewPath}";
    }

    inspectHtmlTags($contents, $relativeViewPath, $errors);
    $tailwindColorPattern = '/(?<![\w-])'
        .'(?:hover:|focus:|focus-visible:|active:|disabled:|sm:|md:|lg:|xl:|2xl:)*'
        .'(?:text|bg|border|divide|ring|placeholder|from|via|to)-'
        .'(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|'
        .'violet|purple|fuchsia|pink|rose|white|black)(?:-[0-9]{2,3})?(?:\/[0-9]+)?(?![\w-])/u';
    if (preg_match($tailwindColorPattern, $contents, $colorMatch) === 1) {
        $errors[] = "Tailwindの色ユーティリティが残っています: {$relativeViewPath} class={$colorMatch[0]}";
    }

    // CSSの影響範囲を追跡できなくなるため、Blade内のインラインstyleを禁止する。
    if (preg_match('/\sstyle\s*=/i', $contents) === 1) {
        $errors[] = "インラインstyleが残っています: {$relativeViewPath}";
    }

    if (preg_match('/<style\b/i', $contents) === 1) {
        $errors[] = "styleタグが残っています: {$relativeViewPath}";
    }

    preg_match_all('/class="([^"]*)"/u', $contents, $classMatches);
    foreach ($classMatches[1] as $classValue) {
        $classes = preg_split('/\s+/u', trim($classValue)) ?: [];
        if (in_array('bg-slate-900', $classes, true)
            || in_array('hover:bg-slate-700', $classes, true)) {
            $errors[] = "旧主要ボタン色のユーティリティが残っています: {$relativeViewPath}";
        }

        if (in_array('bg-white', $classes, true)
            && (in_array('rounded-2xl', $classes, true) || in_array('rounded-xl', $classes, true))) {
            $errors[] = "互換CSSを前提としたカード指定が残っています: {$relativeViewPath}";
        }
    }

    $usesAuthenticatedLayout = str_contains($contents, "@extends('layouts.app')");
    $isErrorPage = preg_match(
        '#/resources/views/errors/\d{3}\.blade\.php$#',
        str_replace('\\', '/', $viewPath),
    ) === 1;

    if (! $usesAuthenticatedLayout && ! $isErrorPage) {
        continue;
    }

    if ($usesAuthenticatedLayout
        && preg_match("/@section\('page-class', '([^']+)'\)/", $contents) !== 1) {
        $errors[] = "page-classが設定されていません: {$relativeViewPath}";
    }

    $hasPageStyle = preg_match(
        "/@section\('page-style', '([^']+)'\)/",
        $contents,
        $styleMatch,
    ) === 1;

    if (! $hasPageStyle) {
        if ($isErrorPage) {
            $errors[] = "page-styleが設定されていません: {$relativeViewPath}";
        }

        continue;
    }

    $cssPath = $projectRoot.'/'.$styleMatch[1];
    $pageStyleReferences[] = $cssPath;

    if (! is_file($cssPath)) {
        $errors[] = "ページCSSが存在しません: {$relativeViewPath} -> {$cssPath}";
    }
}

$tokenPath = realpath($cssRoot.'/core/tokens.css');

foreach (collectFiles($cssRoot, '.css') as $cssPath) {
    $contents = file_get_contents($cssPath);

    if ($contents === false) {
        $errors[] = "CSSを読み込めません: {$cssPath}";

        continue;
    }

    $relativeCssPath = str_replace($projectRoot.'/', '', str_replace('\\', '/', $cssPath));

    if (str_contains($contents, '!important')) {
        $errors[] = "!importantが含まれています: {$relativeCssPath}";
    }

    if (preg_match('/\/\*\s*(微調整|最終修正|FINAL FIX|強制上書き)/u', $contents) === 1) {
        $errors[] = "禁止コメントが含まれています: {$relativeCssPath}";
    }

    $withoutComments = preg_replace('#/\*.*?\*/#s', '', $contents);
    if (! is_string($withoutComments)) {
        $errors[] = "CSSコメントを除去できません: {$relativeCssPath}";

        continue;
    }

    if (realpath($cssPath) !== $tokenPath
        && preg_match('/#[0-9a-f]{3,8}\b|rgba?\s*\(|hsla?\s*\(/iu', $withoutComments, $colorMatch) === 1) {
        $errors[] = "色がトークン外へ直接記述されています: {$relativeCssPath} color={$colorMatch[0]}";
    }

    inspectCssRules($withoutComments, $relativeCssPath, $selectorOrigins, $errors);

    preg_match_all('/@import\s+[\'\"]([^\'\"]+)[\'\"]/', $contents, $imports);
    $cssImportGraph[$cssPath] = [];

    foreach ($imports[1] as $importPath) {
        if ($importPath === 'tailwindcss') {
            continue;
        }

        $resolvedPath = realpath(dirname($cssPath).'/'.$importPath);
        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            $errors[] = "CSSのimport先が存在しません: {$relativeCssPath} -> {$importPath}";

            continue;
        }

        $cssImportGraph[$cssPath][] = $resolvedPath;
    }

}

/**
 * Vite入力と画面CSSを起点にimportをたどり、実際に利用されるCSSを返す。
 *
 * @param  list<string>  $entryPaths
 * @param  array<string, list<string>>  $cssImportGraph
 * @return array<string, true>
 */
function reachableCssPaths(array $entryPaths, array $cssImportGraph): array
{
    $reachable = [];
    $pending = array_values(array_unique($entryPaths));

    while ($pending !== []) {
        $cssPath = array_pop($pending);

        if (! is_string($cssPath) || isset($reachable[$cssPath]) || ! is_file($cssPath)) {
            continue;
        }

        $reachable[$cssPath] = true;

        foreach ($cssImportGraph[$cssPath] ?? [] as $importPath) {
            if (! isset($reachable[$importPath])) {
                $pending[] = $importPath;
            }
        }
    }

    return $reachable;
}

$viteConfigPath = $projectRoot.'/vite.config.js';
$viteConfig = file_get_contents($viteConfigPath);
$cssEntryPaths = [];

foreach ($pageStyleReferences as $pageStyleReference) {
    $resolvedPath = realpath($pageStyleReference);

    if ($resolvedPath !== false) {
        $cssEntryPaths[] = $resolvedPath;
    }
}

if ($viteConfig === false) {
    $errors[] = 'vite.config.jsを読み込めません。';
} else {
    preg_match_all('#resources/css/[A-Za-z0-9_./-]+\.css#', $viteConfig, $viteCssMatches);

    foreach (array_unique($viteCssMatches[0]) as $viteCssPath) {
        $resolvedPath = realpath($projectRoot.'/'.$viteCssPath);

        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            $errors[] = "Vite入力CSSが存在しません: {$viteCssPath}";

            continue;
        }

        $cssEntryPaths[] = $resolvedPath;
    }
}

$reachableCss = reachableCssPaths($cssEntryPaths, $cssImportGraph);

foreach (collectFiles($cssRoot, '.css') as $cssPath) {
    $resolvedCssPath = realpath($cssPath);

    if ($resolvedCssPath !== false && ! isset($reachableCss[$resolvedCssPath])) {
        $relativeCssPath = str_replace($projectRoot.'/', '', str_replace('\\', '/', $cssPath));
        $errors[] = "参照されていないCSSが残っています: {$relativeCssPath}";
    }
}

$forbiddenCompatibilityPath = $cssRoot.'/components/utility-compatibility.css';
if (is_file($forbiddenCompatibilityPath)) {
    $errors[] = '後勝ち互換CSSが残っています: resources/css/components/utility-compatibility.css';
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
    fwrite(STDERR, implode(PHP_EOL, array_values(array_unique($errors))).PHP_EOL);
    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        'フロントエンド構成チェック完了: ページCSS参照 %d件 / CSS %d件 / 未参照CSS 0件 / '
            .'セレクター再定義 0件 / プロパティ重複 0件%s',
        count($pageStyleReferences),
        count(collectFiles($cssRoot, '.css')),
        PHP_EOL,
    ),
);
