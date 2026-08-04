<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向けユーザー一覧画面を確認するフィーチャーテスト。
 *
 * 一覧表示、ロール絞り込み、キーワード検索、権限制御を検証する。
 */
final class UserListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者がユーザー一覧を閲覧できることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれることを確認する。
     */
    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $targetUser = User::factory()->create([
            'name' => '一覧表示対象ユーザー',
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index'));

        $response
            ->assertOk()
            ->assertSee($targetUser->name)
            ->assertSee($targetUser->email);
    }

    /**
     * 教員が管理者向けユーザー一覧を閲覧できないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.index`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_view_user_list(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    /**
     * 管理者がロールでユーザーを絞り込めることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれる、表示対象外の内容がレスポンスに含まれないことを確認する。
     */
    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        User::factory()->create([
            'name' => '表示対象教員',
            'role' => UserRole::Teacher,
        ]);

        User::factory()->create([
            'name' => '非表示対象生徒',
            'role' => UserRole::Student,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'role' => UserRole::Teacher->value,
            ]));

        $response
            ->assertOk()
            ->assertSee('表示対象教員')
            ->assertDontSee('非表示対象生徒');
    }

    /**
     * 管理者が氏名・メールアドレスのキーワードでユーザーを検索できることを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.users.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれる、表示対象外の内容がレスポンスに含まれないことを確認する。
     */
    public function test_admin_can_search_users_by_keyword(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        User::factory()->create([
            'name' => '検索対象ユーザー',
        ]);

        User::factory()->create([
            'name' => '別ユーザー',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'keyword' => '検索対象',
            ]));

        $response
            ->assertOk()
            ->assertSee('検索対象ユーザー')
            ->assertDontSee('別ユーザー');
    }

    /**
     * PostgreSQLで英字の大文字・小文字を区別せずユーザーを検索できることを確認する。
     *
     * 前提: 大文字を含む氏名のユーザーと管理者を登録する。
     * 処理: 氏名をすべて小文字にしたキーワードでユーザー一覧を検索する。
     * 期待結果: PostgreSQLのILIKE検索により、大文字を含む対象ユーザーが表示されることを確認する。
     */
    public function test_admin_can_search_users_without_case_sensitivity(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        User::factory()->create([
            'name' => 'PostgreSqlTargetUser',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'keyword' => 'postgresqltargetuser',
            ]))
            ->assertOk()
            ->assertSee('PostgreSqlTargetUser');
    }
}
