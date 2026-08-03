<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * アプリケーションの初期データを登録する。
     */
    public function run(): void
    {
        $this->call(ClassGroupSeeder::class);

        if (
            app()->environment(['local', 'testing'])
            && $this->initialAdminIsConfigured()
        ) {
            $this->call(InitialAdminSeeder::class);
        }
    }

    /**
     * 初期管理者の作成に必要な設定がすべて入力されているか判定する。
     */
    private function initialAdminIsConfigured(): bool
    {
        foreach (['name', 'email', 'password'] as $key) {
            $value = config("lms.initial_admin.{$key}");

            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        return true;
    }
}
