<?php

namespace Tests\Unit\Enums;

use App\Enums\Grade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 学年Enumの日本語表示名を確認する単体テスト。
 *
 * 各学年Enumが設計どおりの日本語ラベルを返すことをデータプロバイダーで検証する。
 */
final class GradeTest extends TestCase
{
    /**
     * 各学年Enumと期待する日本語ラベルの組み合わせを返す。
     *
     * @return array<string, array{Grade, string}>
     */
    public static function labelProvider(): array
    {
        return [
            'first grade' => [Grade::First, '1年'],
            'second grade' => [Grade::Second, '2年'],
            'third grade' => [Grade::Third, '3年'],
        ];
    }

    /**
     * 各学年Enumが設計どおりの日本語ラベルを返すことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象Enumの表示名またはログイン可否判定を呼び出す。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    #[DataProvider('labelProvider')]
    public function test_grade_has_japanese_label(
        Grade $grade,
        string $expectedLabel,
    ): void {
        $this->assertSame(
            $expectedLabel,
            $grade->label(),
        );
    }
}
