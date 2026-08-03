<?php

namespace Tests\Feature;

use Illuminate\Foundation\Application;
use Tests\TestCase;

/**
 * ローカル環境初期化コマンドの環境ガードを確認する。
 */
final class SetupLocalCommandTest extends TestCase
{
    /**
     * 本番環境では破壊的なDB初期化を拒否することを確認する。
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
}
