<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasRole
{
    /**
     * 指定されたロールを持つユーザーだけ後続処理へ進める。
     *
     * @param Request $request HTTPリクエスト
     * @param Closure $next 後続処理
     * @param string $roles 許可するロール
     * @return Response 処理結果
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles,
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $allowedRoles = array_map(
            static fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        if (! in_array($user->role, $allowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
