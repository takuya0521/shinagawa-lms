<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 初期管理者を含むデータベースSeederの動作を確認するテスト。
 *
 * 設定不足時のスキップと、必要設定が揃った場合の初期管理者作成を検証する。
 */
final class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 初期管理者設定が不足している場合にSeederが管理者作成をスキップすることを確認する。
     *
     * 前提: 検証対象となる設定値と実行環境を準備する。
     * 処理: Seederを実行し、設定条件に応じた初期データ登録処理を動かす。
     * 期待結果: 関連レコード件数が期待どおりになることを確認する。
     */
    public function test_database_seeder_skips_initial_admin_when_configuration_is_missing(): void
    {
        config([
            'lms.initial_admin.name' => '',
            'lms.initial_admin.email' => '',
            'lms.initial_admin.password' => '',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('class_groups', 2);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 初期管理者設定が揃っている場合にSeederが管理者を作成することを確認する。
     *
     * 前提: 検証対象となる設定値と実行環境を準備する。
     * 処理: Seederを実行し、設定条件に応じた初期データ登録処理を動かす。
     * 期待結果: データベースに期待する内容が保存されることを確認する。
     */
    public function test_database_seeder_creates_initial_admin_when_configuration_is_complete(): void
    {
        config([
            'lms.initial_admin.name' => '初期管理者',
            'lms.initial_admin.email' => 'initial-admin@example.com',
            'lms.initial_admin.password' => 'InitialPassword123',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'name' => '初期管理者',
            'email' => 'initial-admin@example.com',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);
    }
}
