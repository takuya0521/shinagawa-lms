<?php

$projectRoot = dirname(__DIR__);
$targets = [
    $projectRoot.'/app',
    $projectRoot.'/config',
    $projectRoot.'/database',
    $projectRoot.'/resources/css',
    $projectRoot.'/resources/js',
    $projectRoot.'/resources/views',
    $projectRoot.'/routes',
    $projectRoot.'/scripts',
    $projectRoot.'/tests',
];
$extensions = ['css', 'js', 'php'];
$errors = [];
$fileCount = 0;
$lineCount = 0;

foreach ($targets as $target) {
    if (! is_dir($target)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array($file->getExtension(), $extensions, true)) {
            continue;
        }

        $lines = file($file->getPathname());

        if ($lines === false) {
            $errors[] = 'ファイルを読み込めません: '.$file->getPathname();

            continue;
        }

        $fileCount++;
        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot) + 1));

        foreach ($lines as $lineNumber => $line) {
            $lineCount++;
            $line = rtrim($line, "\r\n");
            $characterCount = preg_match_all('/./us', $line, $characters);

            if ($characterCount === false || $characterCount <= 120) {
                continue;
            }

            $errors[] = sprintf(
                '120文字を超えています: %s:%d (%d文字)',
                $relativePath,
                $lineNumber + 1,
                $characterCount,
            );
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
        "行長チェック完了: %dファイル / %d行 / 120文字超過0件\n",
        $fileCount,
        $lineCount,
    ),
);
