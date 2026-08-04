<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$errors = [];
$fileCount = 0;
$classCount = 0;
$testMethodCount = 0;
$helperMethodCount = 0;

/**
 * PHPDocに日本語が含まれているか判定する。
 *
 * @param  string  $docComment  判定対象のPHPDoc
 */
function hasJapaneseTestDocumentation(string $docComment): bool
{
    return preg_match('/[ぁ-んァ-ヶ一-龠々ー]/u', $docComment) === 1;
}

/**
 * テストメソッドのPHPDocに必須の説明区分が含まれているか判定する。
 *
 * @param  string  $docComment  判定対象のPHPDoc
 */
function hasTestFlowSections(string $docComment): bool
{
    foreach (['前提:', '処理:', '期待結果:'] as $section) {
        if (! str_contains($docComment, $section)) {
            return false;
        }
    }

    return true;
}

/**
 * 指定ディレクトリ配下のPHPファイルを相対パス順で取得する。
 *
 * @param  string  $rootPath  検索対象ディレクトリ
 * @return list<string> PHPファイルの絶対パス一覧
 */
function documentationTargetFiles(string $rootPath): array
{
    if (! is_dir($rootPath)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

$targets = [
    [
        'root' => $projectRoot.'/tests',
        'require_class_doc' => true,
        'require_test_flow' => true,
    ],
    [
        'root' => $projectRoot.'/database/factories',
        'require_class_doc' => true,
        'require_test_flow' => false,
    ],
    [
        'root' => $projectRoot.'/database/seeders',
        'require_class_doc' => true,
        'require_test_flow' => false,
    ],
    [
        'root' => $projectRoot.'/database/migrations',
        'require_class_doc' => false,
        'require_test_flow' => false,
    ],
    [
        'root' => $projectRoot.'/scripts',
        'require_class_doc' => false,
        'require_test_flow' => false,
    ],
];

foreach ($targets as $target) {
    foreach (documentationTargetFiles($target['root']) as $path) {
        $source = file_get_contents($path);

        if (! is_string($source)) {
            $errors[] = "ファイルを読み込めません: {$path}";

            continue;
        }

        $fileCount++;
        $relativePath = str_replace('\\', '/', substr($path, strlen($projectRoot) + 1));

        if ($target['require_class_doc']) {
            preg_match_all(
                '/(?<doc>\/\*\*[\s\S]*?\*\/)\s*(?:final\s+|abstract\s+)?class\s+(?<name>[A-Za-z_][A-Za-z0-9_]*)/u',
                $source,
                $documentedClasses,
                PREG_SET_ORDER,
            );
            preg_match_all(
                '/(?:final\s+|abstract\s+)?class\s+(?<name>[A-Za-z_][A-Za-z0-9_]*)/u',
                $source,
                $allClasses,
                PREG_SET_ORDER,
            );

            $classDocs = [];
            foreach ($documentedClasses as $match) {
                $classDocs[$match['name']] = $match['doc'];
            }

            foreach ($allClasses as $match) {
                $classCount++;
                $doc = $classDocs[$match['name']] ?? null;

                if (! is_string($doc) || ! hasJapaneseTestDocumentation($doc)) {
                    $errors[] = "クラスの日本語説明がありません: {$relativePath} {$match['name']}";
                }
            }
        }

        preg_match_all(
            '/(?<doc>\/\*\*[\s\S]*?\*\/)\s*(?:#\[[\s\S]*?\]\s*)*(?:(?:public|protected|private)\s+)?(?:static\s+)?function\s+(?<name>[A-Za-z_][A-Za-z0-9_]*)\s*\(/u',
            $source,
            $documentedMethods,
            PREG_SET_ORDER,
        );
        preg_match_all(
            '/(?:(?:public|protected|private)\s+)?(?:static\s+)?function\s+(?<name>[A-Za-z_][A-Za-z0-9_]*)\s*\(/u',
            $source,
            $allMethods,
            PREG_SET_ORDER,
        );

        $methodDocs = [];
        foreach ($documentedMethods as $match) {
            $methodDocs[$match['name']] = $match['doc'];
        }

        foreach ($allMethods as $match) {
            $methodName = $match['name'];
            $doc = $methodDocs[$methodName] ?? null;

            if (! is_string($doc) || ! hasJapaneseTestDocumentation($doc)) {
                $errors[] = "メソッド・関数の日本語説明がありません: {$relativePath} {$methodName}()";

                continue;
            }

            if ($target['require_test_flow']
                && (str_starts_with($methodName, 'test_') || str_starts_with($methodName, 'it_'))) {
                $testMethodCount++;

                if (! hasTestFlowSections($doc)) {
                    $errors[] = "テストの前提・処理・期待結果が不足しています: {$relativePath} {$methodName}()";
                }
            } else {
                $helperMethodCount++;
            }
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
        "テスト・開発補助コード説明確認完了: %dファイル / %dクラス / %dテストメソッド / %d補助メソッド・関数 / 不足0件\n",
        $fileCount,
        $classCount,
        $testMethodCount,
        $helperMethodCount,
    ),
);
