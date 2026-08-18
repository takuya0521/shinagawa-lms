<?php

$root = dirname(__DIR__);
$viewsRoot = $root.'/resources/views';
$errors = [];
$visibleDevelopmentIds = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsRoot));
foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsRoot) + 1));
    $contents = file_get_contents($file->getPathname());
    if (! is_string($contents)) {
        $errors[] = "読み込み不可: {$relative}";

        continue;
    }

    preg_match_all(
        '/>\s*([CAST]-\d{3}|NFR-\d{3}|CP-\d{3})(?:（[^<]*）)?\s*</u',
        $contents,
        $matches,
    );
    $actualIds = array_values(array_unique($matches[1]));
    if ($actualIds !== []) {
        $visibleDevelopmentIds[$relative] = $actualIds;
    }

    if ($relative !== 'layouts/app.blade.php'
        && preg_match("/session\('(status|success|error)'\)/", $contents) === 1) {
        $errors[] = "共通フィードバックと重複: {$relative}";
    }
}

foreach ($visibleDevelopmentIds as $relative => $ids) {
    $errors[] = sprintf(
        '提出用画面に開発用IDが表示されています: %s ids=%s',
        $relative,
        implode(',', $ids),
    );
}

$externalControllerPath = $root.'/app/Http/Controllers/Student/ExternalServiceController.php';
$externalController = file_get_contents($externalControllerPath);
if (! is_string($externalController)) {
    $errors[] = '生徒外部サービスControllerを読み込めません。';
} elseif (preg_match("/'screenId'\s*=>/", $externalController) === 1) {
    $errors[] = '生徒外部サービスControllerに表示専用のscreenId受け渡しが残っています。';
}

$unusedAssets = [
    $viewsRoot.'/layouts/student-dashboard.blade.php',
    $viewsRoot.'/welcome.blade.php',
    $root.'/resources/css/student-dashboard.css',
    $root.'/resources/css/components/utility-compatibility.css',
];
foreach ($unusedAssets as $unusedAsset) {
    if (is_file($unusedAsset)) {
        $errors[] = '未使用または禁止された資産が残っています: '.str_replace($root.'/', '', $unusedAsset);
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

fwrite(
    STDOUT,
    "画面・共通表示整合チェック完了: 開発用ID表示 0件 / 重複フィードバック 0件 / 不要資産 0件\n",
);
