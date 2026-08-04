<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け教員一覧画面を確認するフィーチャーテスト。
 *
 * 一覧表示、キーワード検索、教員状態・アカウント状態絞り込み、権限制御を検証する。
 */
final class TeacherListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が教員一覧を閲覧できることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_teacher_list(): void
    {
        $admin = $this->createAdmin();

        $teacherUser = $this->createTeacherUser(
            name: '一覧表示教員',
            email: 'teacher-list@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $teacherUser->id,
            'subject_notes' => '数学を担当',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index'));

        $response
            ->assertOk()
            ->assertSeeText('一覧表示教員')
            ->assertSeeText('teacher-list@example.com')
            ->assertSeeText('数学を担当');
    }

    /**
     * 教員が管理者向け教員一覧を閲覧できないことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.teachers.index`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_view_teacher_list(): void
    {
        $teacher = $this->createTeacherUser(
            name: 'アクセス不可教員',
            email: 'forbidden-teacher@example.com',
        );

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.teachers.index'));

        $response->assertForbidden();
    }

    /**
     * 管理者が氏名等のキーワードで教員を検索できることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_search_teacher_by_keyword(): void
    {
        $admin = $this->createAdmin();

        $targetUser = $this->createTeacherUser(
            name: '検索対象教員',
            email: 'target-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $targetUser->id,
            'subject_notes' => '英語担当',
        ]);

        $otherUser = $this->createTeacherUser(
            name: '別の教員',
            email: 'other-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $otherUser->id,
            'subject_notes' => '数学担当',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index', [
                'keyword' => '英語',
            ]));

        $response
            ->assertOk()
            ->assertSeeText('検索対象教員')
            ->assertDontSeeText('別の教員');
    }

    /**
     * 管理者が教員プロフィールの状態で教員を絞り込めることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_filter_teachers_by_teacher_status(): void
    {
        $admin = $this->createAdmin();

        $activeUser = $this->createTeacherUser(
            name: '有効教員',
            email: 'active-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $activeUser->id,
            'status' => MasterStatus::Active,
        ]);

        $inactiveUser = $this->createTeacherUser(
            name: '無効教員',
            email: 'inactive-teacher@example.com',
        );

        Teacher::factory()->create([
            'user_id' => $inactiveUser->id,
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index', [
                'teacher_status' => MasterStatus::Inactive->value,
            ]));

        $response
            ->assertOk()
            ->assertSeeText('無効教員')
            ->assertDontSeeText('有効教員');
    }

    /**
     * 管理者がアカウント状態で教員を絞り込めることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_filter_teachers_by_account_status(): void
    {
        $admin = $this->createAdmin();

        $activeUser = $this->createTeacherUser(
            name: '利用中教員',
            email: 'available-teacher@example.com',
            status: UserStatus::Active,
        );

        Teacher::factory()->create([
            'user_id' => $activeUser->id,
        ]);

        $suspendedUser = $this->createTeacherUser(
            name: '停止中教員',
            email: 'suspended-teacher@example.com',
            status: UserStatus::Suspended,
        );

        Teacher::factory()->create([
            'user_id' => $suspendedUser->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index', [
                'account_status' => UserStatus::Suspended->value,
            ]));

        $response
            ->assertOk()
            ->assertSeeText('停止中教員')
            ->assertDontSeeText('利用中教員');
    }

    /**
     * テストで使用する有効な管理者ユーザーを作成して返す。
     */
    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }

    /**
     * 指定した状態を持つ教員ユーザーと教員プロフィールを作成して返す。
     */
    private function createTeacherUser(
        string $name,
        string $email,
        UserStatus $status = UserStatus::Active,
    ): User {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => UserRole::Teacher,
            'status' => $status,
        ]);
    }
}
