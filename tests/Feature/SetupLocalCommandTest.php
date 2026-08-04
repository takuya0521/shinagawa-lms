<?php

namespace Tests\Feature;

use Illuminate\Foundation\Application;
use Tests\TestCase;

/**
 * ローカル初期化コマンドの安全制御を確認するテスト。
 *
 * 本番環境またはPostgreSQL以外の接続では初期化処理が拒否され、データ破壊を防止できることを検証する。
 */
final class SetupLocalCommandTest extends TestCase
{
    /**
     * ローカル初期化コマンドが本番環境では拒否されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: Artisanコマンド`app:setup-local`を対象環境で実行する。
     * 期待結果: 取得結果が期待する型になることを確認する。
     */
    public function test_setup_local_command_is_rejected_in_production(): void
    {
        $application = $this->app;
        $this->assertInstanceOf(Application::class, $application);

        $originalEnvironment = $application->environment();
        $application->instance('env', 'production');

        try {
            $this
                ->artisan('app:setup-local')
                ->expectsOutput(
                    'このコマンドはlocalまたはtesting環境でのみ実行できます。',
                )
                ->assertFailed();
        } finally {
            $application->instance('env', $originalEnvironment);
        }
    }

    /**
     * PostgreSQL以外の接続ではローカル初期化コマンドが拒否されることを確認する。
     *
     * 前提: ローカル環境で既定DB接続をPostgreSQL以外へ変更する。
     * 処理: Artisanコマンド`app:setup-local`を実行する。
     * 期待結果: PostgreSQL接続を求めるエラーが表示され、初期化処理が開始されないことを確認する。
     */
    public function test_setup_local_command_requires_postgresql_connection(): void
    {
        $originalConnection = config('database.default');
        config()->set('database.default', 'sqlite');

        try {
            $this
                ->artisan('app:setup-local')
                ->expectsOutput(
                    'DB_CONNECTIONをpgsqlに設定してから実行してください。',
                )
                ->assertFailed();
        } finally {
            config()->set('database.default', $originalConnection);
        }
    }
}
