<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * PostgreSQLを使用するローカル・テスト環境だけで初期DBを再構築する。
 */
final class SetupLocalCommand extends Command
{
    /**
     * コマンド名と説明を定義する。
     *
     * @var string
     */
    protected $signature = 'app:setup-local';

    /**
     * コマンド一覧へ表示する説明を定義する。
     *
     * @var string
     */
    protected $description = 'PostgreSQLのローカルデータベースを初期化し、初期データを登録します。';

    /**
     * ローカル・テスト環境かつPostgreSQL接続でのみDBを再構築する。
     *
     * @return int コマンド終了コード
     */
    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error(
                'このコマンドはlocalまたはtesting環境でのみ実行できます。',
            );

            return self::FAILURE;
        }

        if (config('database.default') !== 'pgsql') {
            $this->error(
                'DB_CONNECTIONをpgsqlに設定してから実行してください。',
            );

            return self::FAILURE;
        }

        if (! extension_loaded('pdo_pgsql')) {
            $this->error(
                'PHP拡張pdo_pgsqlを有効化してから実行してください。',
            );

            return self::FAILURE;
        }

        $this->warn(
            '接続先PostgreSQLデータベースの全テーブルを削除して再作成します。',
        );

        $exitCode = $this->call('migrate:fresh', [
            '--seed' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('PostgreSQLデータベースの初期化が完了しました。');

        return self::SUCCESS;
    }
}
