<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    /**
     * 利用中のユーザーだけ後続処理へ進める。
     *
     * @param Request $request HTTPリクエスト
     * @param Closure $next 後続処理
     * @return Response 処理結果
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isActive()) {
            // ログイン後に利用停止へ変更された場合も、現在のセッションを無効化する。
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'このアカウントは現在利用できません。',
                ]);
        }

        return $next($request);
    }
}
