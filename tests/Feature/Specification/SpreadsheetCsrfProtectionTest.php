<?php

namespace Tests\Feature\Specification;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 単体テスト仕様書の「不正な送信の拒否」がLaravel標準のCSRF保護下にあることを確認する。
 */
final class SpreadsheetCsrfProtectionTest extends TestCase
{
    /**
     * Excelのテスト番号ごとに対象画面がwebミドルウェア配下であり、
     * webグループにCSRF検証ミドルウェアが含まれることを確認する。
     *
     * @param  array<string, mixed>  $case
     */
    #[DataProvider('spreadsheetCases')]
    public function test_spreadsheet_csrf_case_is_protected(array $case): void
    {
        $route = $this->app['router']
            ->getRoutes()
            ->getByName((string) $case['route']);

        self::assertInstanceOf(
            Route::class,
            $route,
            $case['case_id'].' の対象ルートが存在しません。',
        );

        self::assertContains(
            'web',
            $route->gatherMiddleware(),
            $case['case_id'].' の対象画面がwebミドルウェア配下ではありません。',
        );

        $groups = $this->app['router']->getMiddlewareGroups();
        self::assertArrayHasKey('web', $groups);
        self::assertContains(
            PreventRequestForgery::class,
            $groups['web'],
            'webミドルウェアグループにCSRF検証がありません。',
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function spreadsheetCases(): array
    {
        /** @var array<string, array<string, mixed>> $cases */
        $cases = require dirname(__DIR__, 2).'/Fixtures/spreadsheet_csrf_cases.php';
        $datasets = [];

        foreach ($cases as $caseId => $case) {
            $datasets[$caseId] = [$case];
        }

        return $datasets;
    }
}
