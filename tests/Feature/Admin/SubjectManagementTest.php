<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け科目管理機能を確認するフィーチャーテスト。
 *
 * 画面表示、登録・更新、科目コード検証、検索・絞り込み、権限制御を検証する。
 */
final class SubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が科目一覧を閲覧できることを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_subject_list(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'MATH',
            'subject_name' => '数学',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertSeeText(
                $subject->subject_code,
            )
            ->assertSeeText(
                $subject->subject_name,
            );
    }

    /**
     * 管理者が科目登録画面を表示できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.subjects.create`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_subject_create_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $this
            ->actingAs($admin)
            ->get(route('admin.subjects.create'))
            ->assertOk()
            ->assertSeeText('科目登録')
            ->assertSeeText('科目コード')
            ->assertSeeText('科目名');
    }

    /**
     * 管理者が科目を登録できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.subjects.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_create_subject(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.subjects.store'), [
                'subject_code' => ' eng ',
                'subject_name' => ' 英語 ',
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(
                route('admin.subjects.index'),
            )
            ->assertSessionHas(
                'status',
                '科目を登録しました。',
            );

        $this->assertDatabaseHas('subjects', [
            'subject_code' => 'ENG',
            'subject_name' => '英語',
            'status' => MasterStatus::Active->value,
        ]);
    }

    /**
     * 形式が不正な科目コードを登録できないことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.subjects.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返る、不要なデータがデータベースに保存されないことを確認する。
     */
    public function test_invalid_subject_code_cannot_be_created(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.subjects.create'))
            ->post(route('admin.subjects.store'), [
                'subject_code' => '科目01',
                'subject_name' => '国語',
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(
                route('admin.subjects.create'),
            )
            ->assertSessionHasErrors(
                'subject_code',
            );

        $this->assertDatabaseMissing('subjects', [
            'subject_code' => '科目01',
        ]);
    }

    /**
     * 同じ科目コードを重複登録できないことを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_duplicate_subject_code_cannot_be_created(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        Subject::factory()->create([
            'subject_code' => 'JAPANESE',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.subjects.create'))
            ->post(route('admin.subjects.store'), [
                'subject_code' => 'japanese',
                'subject_name' => '国語',
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(
                route('admin.subjects.create'),
            )
            ->assertSessionHasErrors(
                'subject_code',
            );
    }

    /**
     * 管理者が科目編集画面を表示できることを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.edit`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、必要な内容がレスポンスに含まれることを確認する。
     */
    public function test_admin_can_view_subject_edit_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'SCIENCE',
            'subject_name' => '理科',
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.subjects.edit',
                    $subject,
                ),
            )
            ->assertOk()
            ->assertSeeText('科目編集')
            ->assertSee(
                $subject->subject_code,
            )
            ->assertSee(
                $subject->subject_name,
            );
    }

    /**
     * 管理者が科目情報を更新できることを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_update_subject(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'SCIENCE',
            'subject_name' => '理科',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.subjects.update',
                    $subject,
                ),
                [
                    'subject_code' => ' science-01 ',
                    'subject_name' => ' 科学 ',
                    'status' => MasterStatus::Inactive->value,
                ],
            );

        $response
            ->assertRedirect(
                route('admin.subjects.index'),
            )
            ->assertSessionHas(
                'status',
                '科目を更新しました。',
            );

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'subject_code' => 'SCIENCE-01',
            'subject_name' => '科学',
            'status' => MasterStatus::Inactive->value,
        ]);
    }

    /**
     * 科目コードを変更せずに科目情報を更新できることを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_update_subject_without_changing_code(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'MATH',
            'subject_name' => '数学',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.subjects.update',
                    $subject,
                ),
                [
                    'subject_code' => 'MATH',
                    'subject_name' => '数学Ⅰ',
                    'status' => MasterStatus::Active->value,
                ],
            );

        $response->assertRedirect(
            route('admin.subjects.index'),
        );

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'subject_code' => 'MATH',
            'subject_name' => '数学Ⅰ',
        ]);
    }

    /**
     * 管理者が科目名のキーワードで科目を検索できることを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_search_subjects_by_keyword(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $matchingSubject = Subject::factory()->create([
            'subject_code' => 'MATH',
            'subject_name' => '数学',
        ]);

        $otherSubject = Subject::factory()->create([
            'subject_code' => 'ENGLISH',
            'subject_name' => '英語',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index', [
                'keyword' => '数学',
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $matchingSubject->subject_name,
            )
            ->assertDontSeeText(
                $otherSubject->subject_name,
            );
    }

    /**
     * 管理者が科目コードで科目を検索できることを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_search_subjects_by_code(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $matchingSubject = Subject::factory()->create([
            'subject_code' => 'MATH-01',
            'subject_name' => '数学',
        ]);

        $otherSubject = Subject::factory()->create([
            'subject_code' => 'ENGLISH-01',
            'subject_name' => '英語',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index', [
                'keyword' => 'MATH',
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $matchingSubject->subject_code,
            )
            ->assertDontSeeText(
                $otherSubject->subject_code,
            );
    }

    /**
     * 管理者が有効・無効状態で科目を絞り込めることを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_filter_subjects_by_status(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $activeSubject = Subject::factory()->create([
            'subject_code' => 'ACTIVE-SUBJECT',
            'subject_name' => '有効科目',
            'status' => MasterStatus::Active,
        ]);

        $inactiveSubject = Subject::factory()->create([
            'subject_code' => 'INACTIVE-SUBJECT',
            'subject_name' => '無効科目',
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index', [
                'status' => MasterStatus::Inactive->value,
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $inactiveSubject->subject_name,
            )
            ->assertDontSeeText(
                $activeSubject->subject_name,
            );
    }

    /**
     * 教員が科目管理機能を利用できないことを確認する。
     *
     * 前提: 科目など、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.index`へGETリクエスト、`admin.subjects.create`へGETリクエスト、`admin.subjects.store`へPOSTリクエスト、関連する後続リクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_manage_subjects(): void
    {
        $teacher = $this->createUser(
            UserRole::Teacher,
        );

        $subject = Subject::factory()->create();

        $this
            ->actingAs($teacher)
            ->get(route('admin.subjects.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->get(route('admin.subjects.create'))
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->post(route('admin.subjects.store'), [
                'subject_code' => 'MATH',
                'subject_name' => '数学',
                'status' => MasterStatus::Active->value,
            ])
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->get(
                route(
                    'admin.subjects.edit',
                    $subject,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->put(
                route(
                    'admin.subjects.update',
                    $subject,
                ),
                [
                    'subject_code' => 'MATH',
                    'subject_name' => '数学',
                    'status' => MasterStatus::Active->value,
                ],
            )
            ->assertForbidden();
    }

    /**
     * 指定したロールと状態を持つテスト用ユーザーを作成して返す。
     */
    private function createUser(
        UserRole $role,
    ): User {
        return User::factory()->create([
            'role' => $role,
            'status' => UserStatus::Active,
        ]);
    }
}
