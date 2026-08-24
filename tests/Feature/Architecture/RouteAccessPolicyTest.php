<?php

namespace Tests\Feature\Architecture;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * LMSの画面・更新ルートに必要な認証／ロール制御が設定されていることを確認する。
 */
final class RouteAccessPolicyTest extends TestCase
{
    /** 管理者・教員・生徒・Google Workspaceの各ルートに必要なアクセス制御があることを確認する。 */
    public function test_protected_routes_have_expected_middleware(): void
    {
        foreach (RouteFacade::getRoutes() as $route) {
            $name = (string) $route->getName();
            if ($name === '') {
                continue;
            }

            if (str_starts_with($name, 'admin.')) {
                $this->assertMiddleware($route, 'auth');
                $this->assertMiddleware($route, 'active');
                $this->assertMiddleware($route, 'role:admin');
            }

            if (str_starts_with($name, 'teacher.')) {
                $this->assertMiddleware($route, 'auth');
                $this->assertMiddleware($route, 'active');
                $this->assertMiddleware($route, 'role:teacher');
            }

            if (str_starts_with($name, 'student.') && $name !== 'student.google-drive.callback') {
                $this->assertMiddleware($route, 'auth');
                $this->assertMiddleware($route, 'active');
                $this->assertMiddleware($route, 'role:student');
            }

            if (str_starts_with($name, 'google-workspace.')) {
                $this->assertMiddleware($route, 'auth');
                $this->assertMiddleware($route, 'active');
            }
        }
    }

    private function assertMiddleware(Route $route, string $expected): void
    {
        $middleware = $route->gatherMiddleware();
        $this->assertContains(
            $expected,
            $middleware,
            sprintf('ルート %s に %s ミドルウェアがありません。', $route->getName(), $expected),
        );
    }
}
