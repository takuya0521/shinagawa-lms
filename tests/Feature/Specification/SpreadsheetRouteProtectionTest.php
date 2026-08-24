<?php

namespace Tests\Feature\Specification;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 単体テスト仕様書の未ログイン・権限制御観点がLaravelルート定義で保護されていることを確認する。
 */
final class SpreadsheetRouteProtectionTest extends TestCase
{
    /**
     * Excelの認証・認可テスト番号ごとに、auth・active・roleミドルウェアを確認する。
     *
     * @param  array<string, mixed>  $case
     */
    #[DataProvider('spreadsheetCases')]
    public function test_spreadsheet_route_protection_is_implemented(array $case): void
    {
        $route = $this->app['router']
            ->getRoutes()
            ->getByName((string) $case['route']);

        self::assertInstanceOf(
            Route::class,
            $route,
            $case['case_id'].' の対象ルートが存在しません。',
        );

        $middleware = $route->gatherMiddleware();

        self::assertContains(
            'auth',
            $middleware,
            $case['case_id'].' の対象ルートにauthミドルウェアがありません。',
        );
        self::assertContains(
            'active',
            $middleware,
            $case['case_id'].' の対象ルートにactiveミドルウェアがありません。',
        );

        $role = $case['role'];
        if (is_string($role) && $role !== '') {
            self::assertContains(
                'role:'.$role,
                $middleware,
                $case['case_id'].' の対象ルートに必要なロール制御がありません。',
            );
        }
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function spreadsheetCases(): array
    {
        /** @var array<string, array<string, mixed>> $cases */
        $cases = require dirname(__DIR__, 2).'/Fixtures/spreadsheet_route_cases.php';
        $datasets = [];

        foreach ($cases as $caseId => $case) {
            $datasets[$caseId] = [$case];
        }

        return $datasets;
    }
}
