<?php

$projectRoot = dirname(__DIR__);
$scanDirectories = [
    'app',
    'config',
    'database',
    'routes',
    'tests',
    'scripts',
    'resources',
    'public',
];
$errors = [];

/**
 * 指定ディレクトリ配下の確認対象ファイルを取得する。
 *
 * @param  string  $directory  確認対象ディレクトリ
 * @return list<string> 確認対象ファイル一覧
 */
function collectCommentTargetFiles(string $directory): array
{
    $extensions = ['php', 'css', 'js'];
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS,
        ),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $normalizedPath = str_replace('\\', '/', $path);

        // Viteが生成したビルド成果物はソースコメントの検査対象外とする。
        if (str_contains($normalizedPath, '/public/build/')) {
            continue;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (
            in_array($extension, $extensions, true)
            || str_ends_with($path, '.blade.php')
        ) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

/**
 * コメント内に日本語の文字が含まれているかを判定する。
 *
 * @param  string  $comment  確認対象コメント
 * @return bool 日本語を含む場合はtrue
 */
function containsJapanese(string $comment): bool
{
    return preg_match('/[ぁ-んァ-ヶ一-龠々ー]/u', $comment) === 1;
}

/**
 * PHPDocタグや静的解析指示だけのコメントかを判定する。
 *
 * @param  string  $comment  確認対象コメント
 * @return bool 説明文を含まない技術コメントの場合はtrue
 */
function isTechnicalOnlyComment(string $comment): bool
{
    $lines = preg_split('/\R/u', $comment) ?: [];
    $humanLines = [];

    foreach ($lines as $line) {
        $line = trim($line);
        $line = preg_replace('#^/\*+|\*/$#', '', $line) ?? $line;
        $line = ltrim($line, "* \t");
        $line = preg_replace('#^(?://|<!--|-->)\s*#', '', $line) ?? $line;

        if ($line === '') {
            continue;
        }

        if (
            str_starts_with($line, '@')
            || str_starts_with($line, 'phpcs:')
            || str_starts_with($line, 'phpstan:')
            || str_starts_with($line, 'noinspection')
            || preg_match('/^(TODO|FIXME|HACK)(:|$)/i', $line) === 1
            || str_starts_with($line, "'")
            || str_starts_with($line, '"')
            || str_starts_with($line, '$')
            || str_starts_with($line, '[')
            || str_starts_with($line, ']')
            || (str_contains($line, '::') && str_contains($line, '=>'))
        ) {
            continue;
        }

        $humanLines[] = $line;
    }

    return $humanLines === [];
}

/**
 * 英語だけで記載された人間向けコメントかを判定する。
 *
 * @param  string  $comment  確認対象コメント
 * @return bool 英語だけの説明コメントの場合はtrue
 */
function isEnglishOnlyHumanComment(string $comment): bool
{
    if (containsJapanese($comment) || isTechnicalOnlyComment($comment)) {
        return false;
    }

    return preg_match('/[A-Za-z]{3,}/', $comment) === 1;
}

/**
 * PHPファイルからコメントと行番号を取得する。
 *
 * @param  string  $contents  PHPファイル内容
 * @return list<array{line: int, comment: string}> コメント一覧
 */
function phpComments(string $contents): array
{
    $comments = [];

    foreach (token_get_all($contents) as $token) {
        if (! is_array($token)) {
            continue;
        }

        if (! in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $comments[] = [
            'line' => $token[2],
            'comment' => $token[1],
        ];
    }

    return $comments;
}

/**
 * CSS・JavaScript・Bladeファイルからコメントと行番号を取得する。
 *
 * @param  string  $contents  ファイル内容
 * @return list<array{line: int, comment: string}> コメント一覧
 */
function textComments(string $contents): array
{
    $comments = [];
    $patterns = [
        '/\/\*.*?\*\//su',
        '/<!--.*?-->/su',
        '/^\s*\/\/.*$/mu',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($matches[0] as [$comment, $offset]) {
                $comments[] = [
                    'line' => substr_count(substr($contents, 0, $offset), "\n") + 1,
                    'comment' => $comment,
                ];
            }
        }
    }

    return $comments;
}

foreach ($scanDirectories as $relativeDirectory) {
    $directory = $projectRoot.'/'.$relativeDirectory;

    if (! is_dir($directory)) {
        continue;
    }

    foreach (collectCommentTargetFiles($directory) as $path) {
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            $errors[] = "ファイルを読み込めません: {$path}";

            continue;
        }

        $comments = str_ends_with($path, '.php')
            ? phpComments($contents)
            : textComments($contents);

        foreach ($comments as $comment) {
            if (! isEnglishOnlyHumanComment($comment['comment'])) {
                continue;
            }

            $relativePath = str_replace(
                '\\',
                '/',
                substr($path, strlen($projectRoot) + 1),
            );
            $summary = preg_replace('/\s+/u', ' ', trim($comment['comment']));
            $errors[] = sprintf(
                '英語だけの説明コメントがあります: %s:%d %s',
                $relativePath,
                $comment['line'],
                substr((string) $summary, 0, 120),
            );
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "日本語コメント確認完了: 英語だけの説明コメント 0件\n");
