<?php

namespace Tests\Feature\Admin;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\ExternalLink;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け外部リンク管理機能を確認するフィーチャーテスト。
 *
 * 一覧表示、公開範囲別登録、URL・公開範囲検証、更新、権限制御を検証する。
 */
final class ExternalLinkManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が外部リンク一覧を閲覧できることを確認する。
     *
     * 前提: 外部リンクなど、検証に必要なテストデータを準備する。
     * 処理: `admin.external-links.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_external_link_list(): void
    {
        $admin = $this->admin();
        $externalLink = ExternalLink::factory()->create([
            'link_name' => '年間予定',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.external-links.index'))
            ->assertOk()
            ->assertSeeText('外部リンク管理')
            ->assertSeeText($externalLink->link_name);
    }

    /**
     * 管理者が全体公開の外部リンクを登録できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.external-links.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_create_global_external_link(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.external-links.store'), [
                'link_type' => ExternalLinkType::Calendar->value,
                'link_name' => ' 年間予定 ',
                'url' => ' https://calendar.google.com/calendar/embed?src=school ',
                'scope_type' => ExternalLinkScopeType::Global->value,
                'role_scope_id' => null,
                'class_group_scope_id' => null,
                'course_scope_id' => null,
                'student_scope_id' => null,
                'display_order' => 10,
                'status' => MasterStatus::Active->value,
            ]);

        $externalLink = ExternalLink::query()->firstOrFail();

        $response
            ->assertRedirect(route('admin.external-links.edit', $externalLink))
            ->assertSessionHas('success', '外部リンクを登録しました。');

        $this->assertDatabaseHas('external_links', [
            'id' => $externalLink->id,
            'link_type' => ExternalLinkType::Calendar->value,
            'link_name' => '年間予定',
            'url' => 'https://calendar.google.com/calendar/embed?src=school',
            'scope_type' => ExternalLinkScopeType::Global->value,
            'scope_id' => null,
            'display_order' => 10,
            'status' => MasterStatus::Active->value,
        ]);
        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'create_external_link',
            'target_table' => 'external_links',
            'target_id' => $externalLink->id,
        ]);
    }

    /**
     * 管理者がロール単位・クラス単位の外部リンクを登録できることを確認する。
     *
     * 前提: クラスなど、検証に必要なテストデータを準備する。
     * 処理: `admin.external-links.store`へPOSTリクエストを送信する。
     * 期待結果: バリデーションエラーが発生しない、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_create_role_and_class_scoped_links(): void
    {
        $admin = $this->admin();
        $classGroup = ClassGroup::factory()->create();

        $this
            ->actingAs($admin)
            ->post(route('admin.external-links.store'), [
                'link_type' => ExternalLinkType::Chat->value,
                'link_name' => '生徒連絡用Chat',
                'url' => 'https://chat.google.com/room/student',
                'scope_type' => ExternalLinkScopeType::Role->value,
                'role_scope_id' => UserRole::Student->scopeId(),
                'display_order' => 1,
                'status' => MasterStatus::Active->value,
            ])
            ->assertSessionHasNoErrors();

        $this
            ->actingAs($admin)
            ->post(route('admin.external-links.store'), [
                'link_type' => ExternalLinkType::Drive->value,
                'link_name' => 'クラス共有Drive',
                'url' => 'https://drive.google.com/drive/folders/class',
                'scope_type' => ExternalLinkScopeType::ClassGroup->value,
                'class_group_scope_id' => $classGroup->id,
                'display_order' => 2,
                'status' => MasterStatus::Active->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('external_links', [
            'scope_type' => ExternalLinkScopeType::Role->value,
            'scope_id' => UserRole::Student->scopeId(),
        ]);
        $this->assertDatabaseHas('external_links', [
            'scope_type' => ExternalLinkScopeType::ClassGroup->value,
            'scope_id' => $classGroup->id,
        ]);
    }

    /**
     * HTTPS以外のURLと不正な公開範囲が拒否されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.external-links.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_non_https_url_and_invalid_scope_are_rejected(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->from(route('admin.external-links.create'))
            ->post(route('admin.external-links.store'), [
                'link_type' => ExternalLinkType::Forms->value,
                'link_name' => '不正リンク',
                'url' => 'http://forms.google.com/example',
                'scope_type' => ExternalLinkScopeType::ClassGroup->value,
                'class_group_scope_id' => 999999,
                'display_order' => 0,
                'status' => MasterStatus::Active->value,
            ])
            ->assertRedirect(route('admin.external-links.create'))
            ->assertSessionHasErrors([
                'url',
                'scope_id',
            ]);
    }

    /**
     * 管理者が外部リンクを更新できることを確認する。
     *
     * 前提: 外部リンクなど、検証に必要なテストデータを準備する。
     * 処理: `admin.external-links.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_update_external_link(): void
    {
        $admin = $this->admin();
        $externalLink = ExternalLink::factory()->create();

        $this
            ->actingAs($admin)
            ->put(route('admin.external-links.update', $externalLink), [
                'link_type' => ExternalLinkType::Forms->value,
                'link_name' => '面談希望フォーム',
                'url' => 'https://docs.google.com/forms/d/example/viewform',
                'scope_type' => ExternalLinkScopeType::Role->value,
                'role_scope_id' => UserRole::Student->scopeId(),
                'display_order' => 3,
                'status' => MasterStatus::Inactive->value,
            ])
            ->assertRedirect(route('admin.external-links.edit', $externalLink));

        $this->assertDatabaseHas('external_links', [
            'id' => $externalLink->id,
            'link_type' => ExternalLinkType::Forms->value,
            'link_name' => '面談希望フォーム',
            'scope_type' => ExternalLinkScopeType::Role->value,
            'scope_id' => UserRole::Student->scopeId(),
            'status' => MasterStatus::Inactive->value,
        ]);
        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'update_external_link',
            'target_table' => 'external_links',
            'target_id' => $externalLink->id,
        ]);
    }

    /**
     * 教員が外部リンク管理機能を利用できないことを確認する。
     *
     * 前提: 教員、外部リンクなど、検証に必要なテストデータを準備する。
     * 処理: `admin.external-links.index`へGETリクエスト、`admin.external-links.create`へGETリクエスト、`admin.external-links.edit`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_manage_external_links(): void
    {
        $teacher = Teacher::factory()->create();
        $externalLink = ExternalLink::factory()->create();

        $this->actingAs($teacher->user)
            ->get(route('admin.external-links.index'))
            ->assertForbidden();
        $this->actingAs($teacher->user)
            ->get(route('admin.external-links.create'))
            ->assertForbidden();
        $this->actingAs($teacher->user)
            ->get(route('admin.external-links.edit', $externalLink))
            ->assertForbidden();
    }

    /**
     * テストで使用する有効な管理者ユーザーを作成して返す。
     */
    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
