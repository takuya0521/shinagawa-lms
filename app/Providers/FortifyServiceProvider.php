<?php

namespace App\Providers;

use App\Enums\UserStatus;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
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

        // 利用停止状態とパスワード文字種を確認したうえで認証する。
        Fortify::authenticateUsing(function (Request $request): ?User {
            $email = Str::lower(
                trim((string) $request->input('email'))
            );
            $password = (string) $request->input('password');

            $user = User::query()
                ->where('email', $email)
                ->first();

            if ($user === null) {
                return null;
            }

            if ($user->status !== UserStatus::Active) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'このアカウントは現在利用できません',
                ]);
            }

            // ログインパスワードは半角の印字可能ASCII文字のみ受け付ける。
            if (preg_match('/[^\x20-\x7E]/', $password) === 1) {
                throw ValidationException::withMessages([
                    'password' => 'パスワードは半角文字で入力してください',
                ]);
            }

            if (! Hash::check($password, $user->password)) {
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
