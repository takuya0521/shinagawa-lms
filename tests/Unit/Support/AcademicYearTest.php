<?php

namespace Tests\Unit\Support;

use App\Support\AcademicYear;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 日付から年度を算出する共通処理を確認する単体テスト。
 *
 * 年度開始・終了および暦年境界の日付で正しい年度を返すことを検証する。
 */
final class AcademicYearTest extends TestCase
{
    /**
     * 年度境界を含む日付と期待する年度の組み合わせを返す。
     *
     * @return array<string, array{string, int}>
     */
    public static function academicYearProvider(): array
    {
        return [
            'fiscal year start' => ['2026-04-01 00:00:00', 2026],
            'calendar year end' => ['2026-12-31 23:59:59', 2026],
            'calendar year start' => ['2027-01-01 00:00:00', 2026],
            'fiscal year end' => ['2027-03-31 23:59:59', 2026],
        ];
    }

    /**
     * 指定日から正しい年度を算出することを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 指定日を年度算出処理へ渡す。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    #[DataProvider('academicYearProvider')]
    public function test_it_returns_the_academic_year(
        string $dateTime,
        int $expectedAcademicYear,
    ): void {
        $this->assertSame(
            $expectedAcademicYear,
            AcademicYear::forDate(CarbonImmutable::parse($dateTime)),
        );
    }
}
