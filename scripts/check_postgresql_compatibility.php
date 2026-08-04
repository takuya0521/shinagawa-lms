<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$errors = [];
$checkedFiles = 0;

/**
 * 指定ファイルを読み込み、読み込めない場合はエラーへ追加する。
 *
 * @param  string  $path  読み込むファイルの絶対パス
 * @param  list<string>  $errors  エラー一覧
 * @return string ファイル内容。読み込めない場合は空文字列
 */
function postgresqlCheckReadFile(string $path, array &$errors): string
{
    $source = file_get_contents($path);

    if (! is_string($source)) {
        $errors[] = "ファイルを読み込めません: {$path}";

        return '';
    }

    return $source;
}

/**
 * 必須文字列がファイル内に存在することを確認する。
 *
 * @param  string  $relativePath  プロジェクトルートからの相対パス
 * @param  list<string>  $requiredTexts  必須文字列一覧
 * @param  list<string>  $errors  エラー一覧
 * @return void 戻り値なし
 */
function postgresqlCheckRequiredTexts(
    string $relativePath,
    array $requiredTexts,
    array &$errors,
): void {
    global $projectRoot, $checkedFiles;

    $source = postgresqlCheckReadFile(
        $projectRoot.'/'.$relativePath,
        $errors,
    );
    $checkedFiles++;

    foreach ($requiredTexts as $requiredText) {
        if (! str_contains($source, $requiredText)) {
            $errors[] = "PostgreSQL必須設定がありません: {$relativePath} / {$requiredText}";
        }
    }
}

/**
 * 禁止パターンが対象ファイル群に残っていないことを確認する。
 *
 * @param  string  $rootPath  検索対象ディレクトリ
 * @param  string  $pattern  禁止パターンの正規表現
 * @param  string  $description  エラー表示用の説明
 * @param  list<string>  $errors  エラー一覧
 * @return void 戻り値なし
 */
function postgresqlCheckForbiddenPattern(
    string $rootPath,
    string $pattern,
    string $description,
    array &$errors,
): void {
    global $projectRoot, $checkedFiles;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $projectRoot.'/'.$rootPath,
            FilesystemIterator::SKIP_DOTS,
        ),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $source = postgresqlCheckReadFile($file->getPathname(), $errors);
        $checkedFiles++;

        if (preg_match($pattern, $source) === 1) {
            $relativePath = str_replace(
                '\\',
                '/',
                substr($file->getPathname(), strlen($projectRoot) + 1),
            );
            $errors[] = "{$description}: {$relativePath}";
        }
    }
}

postgresqlCheckRequiredTexts(
    '.env.example',
    [
        'DB_CONNECTION=pgsql',
        'DB_PORT=5432',
        'DB_CHARSET=utf8',
        'DB_SEARCH_PATH=public',
        'DB_SSLMODE=prefer',
    ],
    $errors,
);

postgresqlCheckRequiredTexts(
    'config/database.php',
    [
        "env('DB_CONNECTION', 'pgsql')",
        "'driver' => 'pgsql'",
        "env('DB_USERNAME', 'postgres')",
        "env('DB_SEARCH_PATH', 'public')",
    ],
    $errors,
);

postgresqlCheckRequiredTexts(
    'phpunit.xml',
    [
        '<env name="DB_CONNECTION" value="pgsql"/>',
        '<env name="DB_DATABASE" value="shinagawa_lms_testing"/>',
    ],
    $errors,
);

postgresqlCheckRequiredTexts(
    '.github/workflows/tests.yml',
    [
        'image: postgres:18.4-alpine',
        'extensions: pdo_pgsql',
        'php artisan migrate:fresh --seed --force',
    ],
    $errors,
);

postgresqlCheckRequiredTexts(
    'app/Queries/Admin/OperationLogListQuery.php',
    [
        'CAST(operation_logs.detail AS TEXT) ILIKE ?',
    ],
    $errors,
);

postgresqlCheckForbiddenPattern(
    'database/migrations',
    '/\\$table->unsigned[A-Z][A-Za-z]+\\(/',
    'PostgreSQLに存在しないUNSIGNED型指定が残っています',
    $errors,
);

postgresqlCheckForbiddenPattern(
    'database/migrations',
    '/\\$table->year\\(/',
    'PostgreSQL物理設計と異なるYEAR指定が残っています',
    $errors,
);

postgresqlCheckForbiddenPattern(
    'database/migrations',
    '/\\$table->json\\(/',
    'JSONBではなくJSONを使用するMigrationが残っています',
    $errors,
);

postgresqlCheckForbiddenPattern(
    'app/Queries',
    '/[\'\"]like[\'\"]/',
    '大文字・小文字を区別する直接LIKE指定が残っています',
    $errors,
);

if ($errors !== []) {
    fwrite(
        STDERR,
        implode(PHP_EOL, array_values(array_unique($errors))).PHP_EOL,
    );
    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        "PostgreSQL互換性確認完了: %dファイル相当 / 設定・Migration・検索方式の不整合0件\n",
        $checkedFiles,
    ),
);
