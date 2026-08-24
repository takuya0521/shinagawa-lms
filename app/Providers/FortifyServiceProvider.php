<?php

namespace App\Providers;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use App\Support\Auth\LoginCredentialRules;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
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
        // Fortify標準の入力検証をLMSの項目定義に合わせた規則へ差し替える。
        $this->app->bind(
            FortifyLoginRequest::class,
            LoginRequest::class,
        );

        // Fortify標準のintended遷移を使わず、LMSのロール別トップへ統一する。
        $this->app->singleton(
            LoginResponseContract::class,
            LoginResponse::class,
        );
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
            // ログイン画面の入力仕様をサーバー側でも保証する。
            // Fortify標準の必須チェックに加え、詳細設計で定義したメール形式・最大文字数を検証する。
            $request->validate(LoginCredentialRules::rules());

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
