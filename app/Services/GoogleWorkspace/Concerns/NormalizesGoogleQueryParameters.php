<?php

namespace App\Services\GoogleWorkspace\Concerns;

/**
 * Google APIへ送るクエリ内の真偽値を文字列表現へ正規化する。
 * Laravel HTTPクライアントはfalseを0へ変換するため、Google APIが要求する
 * true・false文字列へ統一する。JSON本文は型を維持するため変換対象外とする。
 */
trait NormalizesGoogleQueryParameters
{
    /**
     * @param  array<string, mixed>  $options  HTTPクライアントオプション
     * @return array<string, mixed> 正規化済みオプション
     */
    protected function normalizeGoogleQueryParameters(array $options): array
    {
        return self::normalizeOptions($options);
    }

    /**
     * @param  array<string, mixed>  $options  HTTPクライアントオプション
     * @return array<string, mixed> 正規化済みオプション
     */
    public static function normalizeOptions(array $options): array
    {
        $query = $options['query'] ?? null;

        if (! is_array($query)) {
            return $options;
        }

        $options['query'] = array_map(
            static fn (mixed $value): mixed => is_bool($value)
                ? ($value ? 'true' : 'false')
                : $value,
            $query,
        );

        return $options;
    }
}
