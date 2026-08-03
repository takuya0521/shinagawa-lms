<?php

namespace Tests\Unit\Support;

use App\Support\AcademicYear;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AcademicYearTest extends TestCase
{
    /**
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
