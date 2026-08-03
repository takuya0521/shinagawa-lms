<?php

namespace Tests\Unit\Services;

use App\Services\AnnouncementSanitizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnnouncementSanitizerTest extends TestCase
{
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
