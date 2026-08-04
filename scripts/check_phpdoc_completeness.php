<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$appRoot = $projectRoot.'/app';
$errors = [];

/**
 * トークン配列へ開始位置を付与する。
 *
 * @param  string  $source  PHPソースコード
 * @return list<array{id: int|null, text: string, line: int}> 解析用トークン一覧
 */
function phpDocTokens(string $source): array
{
    $tokens = [];

    foreach (token_get_all($source) as $token) {
        if (is_array($token)) {
            [$id, $text, $line] = $token;
        } else {
            $id = null;
            $text = $token;
            $line = 0;
        }

        $tokens[] = [
            'id' => $id,
            'text' => $text,
            'line' => $line,
        ];
    }

    return $tokens;
}

/**
 * メソッドシグネチャからメソッド名、引数名、戻り値型を取得する。
 *
 * @param  list<array{id: int|null, text: string, line: int}>  $tokens  解析対象トークン一覧
 * @param  int  $functionIndex  T_FUNCTIONトークンの位置
 * @return array{name: string, parameters: list<string>, returnType: string}|null メソッド情報。無名関数の場合はnull
 */
function phpDocMethodSignature(array $tokens, int $functionIndex): ?array
{
    $count = count($tokens);
    $nameIndex = $functionIndex + 1;

    while ($nameIndex < $count) {
        $id = $tokens[$nameIndex]['id'];

        if (! in_array(
            $id,
            [
                T_WHITESPACE,
                T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
                T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
            ],
            true,
        )) {
            break;
        }

        $nameIndex++;
    }

    if ($nameIndex >= $count || $tokens[$nameIndex]['id'] !== T_STRING) {
        return null;
    }

    $openIndex = $nameIndex + 1;

    while ($openIndex < $count && $tokens[$openIndex]['text'] !== '(') {
        $openIndex++;
    }

    $parameters = [];
    $depth = 0;
    $closeIndex = $openIndex;

    for ($index = $openIndex; $index < $count; $index++) {
        $text = $tokens[$index]['text'];

        if ($text === '(') {
            $depth++;

            continue;
        }

        if ($text === ')') {
            $depth--;

            if ($depth === 0) {
                $closeIndex = $index;

                break;
            }

            continue;
        }

        if ($depth === 1 && $tokens[$index]['id'] === T_VARIABLE) {
            $parameters[] = ltrim($text, '$');
        }
    }

    $returnType = 'mixed';
    $index = $closeIndex + 1;

    while ($index < $count && $tokens[$index]['id'] === T_WHITESPACE) {
        $index++;
    }

    if ($index < $count && $tokens[$index]['text'] === ':') {
        $parts = [];
        $index++;

        while ($index < $count && ! in_array($tokens[$index]['text'], ['{', ';'], true)) {
            if ($tokens[$index]['id'] !== T_WHITESPACE) {
                $parts[] = $tokens[$index]['text'];
            }

            $index++;
        }

        $returnType = implode('', $parts) ?: 'mixed';
    }

    return [
        'name' => $tokens[$nameIndex]['text'],
        'parameters' => array_values(array_unique($parameters)),
        'returnType' => $returnType,
    ];
}

/**
 * 戻り値に関するPHPDocの記載が必要か判定する。
 *
 * voidおよびneverは値を返さないため、@returnタグを必須としない。
 *
 * @param  string  $returnType  メソッドの戻り値型
 * @return bool @returnタグが必要な場合はtrue
 */
function phpDocRequiresReturnTag(string $returnType): bool
{
    $normalizedReturnType = strtolower(ltrim($returnType, '\\'));

    return ! in_array($normalizedReturnType, ['void', 'never'], true);
}

$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($appRoot, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}

sort($files);

$methodCount = 0;

foreach ($files as $path) {
    $source = file_get_contents($path);

    if (! is_string($source)) {
        $errors[] = "ファイルを読み込めません: {$path}";

        continue;
    }

    $tokens = phpDocTokens($source);
    $candidateDoc = null;
    $attributeDepth = 0;

    foreach ($tokens as $index => $token) {
        $id = $token['id'];
        $text = $token['text'];

        if ($attributeDepth > 0) {
            if ($text === '[') {
                $attributeDepth++;
            } elseif ($text === ']') {
                $attributeDepth--;
            }

            continue;
        }

        if ($id === T_DOC_COMMENT) {
            $candidateDoc = $text;

            continue;
        }

        if ($id === T_ATTRIBUTE) {
            $attributeDepth = 1;

            continue;
        }

        if (in_array(
            $id,
            [
                T_WHITESPACE,
                T_COMMENT,
                T_PUBLIC,
                T_PROTECTED,
                T_PRIVATE,
                T_STATIC,
                T_FINAL,
                T_ABSTRACT,
                T_READONLY,
            ],
            true,
        )) {
            continue;
        }

        if ($id === T_FUNCTION) {
            $signature = phpDocMethodSignature($tokens, $index);

            if ($signature === null) {
                $candidateDoc = null;

                continue;
            }

            $methodCount++;
            $relativePath = str_replace(
                '\\',
                '/',
                substr($path, strlen($projectRoot) + 1),
            );
            $location = sprintf(
                '%s:%d %s()',
                $relativePath,
                $token['line'],
                $signature['name'],
            );

            if ($candidateDoc === null) {
                $errors[] = "PHPDocがありません: {$location}";

                continue;
            }

            if (preg_match('/[ぁ-んァ-ヶ一-龠々ー]/u', $candidateDoc) !== 1) {
                $errors[] = "PHPDocに日本語説明がありません: {$location}";
            }

            foreach ($signature['parameters'] as $parameter) {
                if (
                    preg_match(
                        '/@param\s+[^\n]*\$'.preg_quote($parameter, '/').'\b/',
                        $candidateDoc,
                    ) !== 1
                ) {
                    $errors[] = "引数の説明がありません: {$location} \${$parameter}";
                }
            }

            if (
                $signature['name'] !== '__construct'
                && phpDocRequiresReturnTag($signature['returnType'])
                && preg_match('/@return\b/', $candidateDoc) !== 1
            ) {
                $errors[] = "戻り値の説明がありません: {$location} 戻り値型={$signature['returnType']}";
            }

            $candidateDoc = null;

            continue;
        }

        $candidateDoc = null;
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);

    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        "PHPDoc確認完了: %dメソッド / 日本語説明・引数・戻り値の不足0件\n",
        $methodCount,
    ),
);
