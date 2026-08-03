<?php

namespace App\Providers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションサービスを登録する。
     *
     * @return void 戻り値なし
     */
    public function register(): void
    {
        //
    }

    /**
     * 認証機能を初期化する。
     *
     * @return void 戻り値なし
     */
    public function boot(): void
    {
        Fortify::loginView(
            fn () => view('auth.login')
        );

        // 利用停止中のユーザーは、パスワードが正しくても認証しない。
        Fortify::authenticateUsing(function (Request $request): ?User {
            $email = Str::lower(
                trim((string) $request->input('email'))
            );

            $user = User::query()
                ->where('email', $email)
                ->first();

            if ($user === null) {
                return null;
            }

            if ($user->status !== UserStatus::Active) {
                return null;
            }

            if (! Hash::check(
                (string) $request->input('password'),
                $user->password,
            )) {
                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(
                Str::lower((string) $request->input(Fortify::username()))
            );

            return Limit::perMinute(5)
                ->by($email.'|'.$request->ip());
        });
    }
}
