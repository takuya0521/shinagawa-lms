<?php

namespace Tests\Unit\Services;

use App\Services\AnnouncementSanitizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * お知らせ本文のサニタイズ処理を確認する単体テスト。
 *
 * HTMLタグと前後空白を除去し、表示可能なプレーンテキストへ変換することを検証する。
 */
final class AnnouncementSanitizerTest extends TestCase
{
    /**
     * サニタイズ処理がHTMLタグと前後の空白を除去することを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: HTMLを含む文字列をサニタイズ処理へ渡す。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    #[Test]
    public function it_removes_html_and_trims_surrounding_whitespace(): void
    {
        $sanitizer = new AnnouncementSanitizer;

        self::assertSame(
            '重要なお知らせ',
            $sanitizer->sanitizePlainText('  <strong>重要な</strong>お知らせ  '),
        );
    }
}
