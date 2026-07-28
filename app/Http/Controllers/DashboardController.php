<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    /**
     * ログインユーザーのロールに対応するダッシュボードへ遷移する。
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        return match ($user->role) {
            UserRole::Admin => redirect()->route('admin.dashboard'),
            UserRole::Teacher => redirect()->route('teacher.dashboard'),
            UserRole::Student => redirect()->route('student.dashboard'),
        };
    }
}
