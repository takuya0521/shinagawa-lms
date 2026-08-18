<?php

namespace App\Providers;

use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Google Workspaceキャッシュをリクエスト・ジョブ単位で共有登録する。
     *
     * 同一リクエスト内ではAPI結果と世代番号をメモリ再利用しつつ、Octaneなどの長寿命workerでは
     * 次リクエストへ状態を持ち越さない。画面間の再利用は永続キャッシュストアへ任せる。
     */
    public function register(): void
    {
        $this->app->scoped(GoogleWorkspaceCache::class);
    }

    /**
     * アプリケーション起動時の初期設定を行う。
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * 本番運用を考慮した共通の既定動作を設定する。
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
