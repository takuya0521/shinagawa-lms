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
        $this->call([
            InitialAdminSeeder::class,
        ]);
    }
}