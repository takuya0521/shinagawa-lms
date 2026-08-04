<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションで使用するサービスを登録する。
     *
     * @return void 戻り値なし
     */
    public function register(): void {}

    /**
     * アプリケーション起動時の初期設定を行う。
     *
     * @return void 戻り値なし
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * 本番運用を考慮した共通の既定動作を設定する。
     *
     * @return void 戻り値なし
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
