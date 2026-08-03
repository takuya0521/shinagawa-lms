<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * ローカル・テスト環境だけで初期DBを再構築する。
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
    protected $description = 'ローカル環境のデータベースを初期化し、初期データを登録します。';

    /**
     * ローカル・テスト環境でのみDBを再構築する。
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

        $this->warn(
            '接続先データベースの全テーブルを削除して再作成します。',
        );

        $exitCode = $this->call('migrate:fresh', [
            '--seed' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('ローカル環境のデータベース初期化が完了しました。');

        return self::SUCCESS;
    }
}
