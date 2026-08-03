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

final class ExternalLinkManagementTest extends TestCase
{
    use RefreshDatabase;

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

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
