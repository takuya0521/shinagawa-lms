<?php

namespace Tests\Unit\Enums;

use App\Enums\Grade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GradeTest extends TestCase
{
    /**
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
