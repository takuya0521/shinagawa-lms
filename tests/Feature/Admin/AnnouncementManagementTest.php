<?php

namespace Tests\Feature\Admin;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\ClassGroup;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AnnouncementManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_announcement_list(): void
    {
        $admin = $this->admin();
        $announcement = Announcement::factory()->create([
            'title' => '夏季休業のお知らせ',
            'created_by' => $admin->id,
        ]);
        $announcement->targets()->create([
            'target_type' => AnnouncementTargetType::All,
            'target_value' => null,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSeeText('掲示板管理')
            ->assertSeeText($announcement->title);
    }

    public function test_admin_can_create_sanitized_announcement_with_targets_and_log(): void
    {
        $admin = $this->admin();
        $classGroup = ClassGroup::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.announcements.store'), [
                'title' => ' 重要なお知らせ ',
                'body' => '<script>alert(1)</script><b>本文</b>',
                'notice_type' => AnnouncementNoticeType::School->value,
                'is_important' => '1',
                'publish_start_at' => '2026-07-29T09:00',
                'publish_end_at' => '2026-08-01T17:00',
                'status' => AnnouncementStatus::Published->value,
                'target_values' => [
                    AnnouncementTargetType::Role->value.':'.UserRole::Student->value,
                    AnnouncementTargetType::Grade->value.':'.Grade::First->value,
                    AnnouncementTargetType::ClassGroup->value.':'.$classGroup->id,
                ],
            ]);

        $announcement = Announcement::query()->firstOrFail();

        $response
            ->assertRedirect(route('admin.announcements.edit', $announcement))
            ->assertSessionHas('success', 'お知らせを登録しました。');

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => '重要なお知らせ',
            'body' => 'alert(1)本文',
            'is_important' => 1,
            'status' => AnnouncementStatus::Published->value,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $this->assertDatabaseCount('announcement_targets', 3);
        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'create_announcement',
            'target_table' => 'announcements',
            'target_id' => $announcement->id,
        ]);
    }

    public function test_announcement_can_have_only_publish_end_and_rejects_invalid_period(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->post(route('admin.announcements.store'), [
                'title' => '終了日のみ指定',
                'body' => '本文',
                'notice_type' => AnnouncementNoticeType::School->value,
                'is_important' => '0',
                'publish_start_at' => null,
                'publish_end_at' => '2026-08-01T17:00',
                'status' => AnnouncementStatus::Published->value,
                'target_values' => [AnnouncementTargetType::All->value],
            ])
            ->assertSessionHasNoErrors();

        $this
            ->actingAs($admin)
            ->from(route('admin.announcements.create'))
            ->post(route('admin.announcements.store'), [
                'title' => '期間不正',
                'body' => '本文',
                'notice_type' => AnnouncementNoticeType::School->value,
                'is_important' => '0',
                'publish_start_at' => '2026-08-02T09:00',
                'publish_end_at' => '2026-08-01T17:00',
                'status' => AnnouncementStatus::Published->value,
                'target_values' => [AnnouncementTargetType::All->value],
            ])
            ->assertRedirect(route('admin.announcements.create'))
            ->assertSessionHasErrors('publish_end_at');
    }

    public function test_all_target_cannot_be_combined_with_other_targets(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->from(route('admin.announcements.create'))
            ->post(route('admin.announcements.store'), [
                'title' => '不正対象',
                'body' => '本文',
                'notice_type' => AnnouncementNoticeType::School->value,
                'is_important' => '0',
                'publish_start_at' => null,
                'publish_end_at' => null,
                'status' => AnnouncementStatus::Draft->value,
                'target_values' => [
                    AnnouncementTargetType::All->value,
                    AnnouncementTargetType::Role->value.':'.UserRole::Student->value,
                ],
            ])
            ->assertRedirect(route('admin.announcements.create'))
            ->assertSessionHasErrors('target_values');
    }

    public function test_admin_can_update_and_delete_announcement(): void
    {
        $admin = $this->admin();
        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'title' => '更新前',
            'status' => AnnouncementStatus::Draft,
        ]);
        $announcement->targets()->create([
            'target_type' => AnnouncementTargetType::All,
            'target_value' => null,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.announcements.update', $announcement), [
                'title' => '更新後',
                'body' => '更新後本文',
                'notice_type' => AnnouncementNoticeType::Office->value,
                'is_important' => '0',
                'publish_start_at' => null,
                'publish_end_at' => null,
                'status' => AnnouncementStatus::Published->value,
                'target_values' => [
                    AnnouncementTargetType::Role->value.':'.UserRole::Teacher->value,
                ],
            ])
            ->assertRedirect(route('admin.announcements.edit', $announcement));

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => '更新後',
            'status' => AnnouncementStatus::Published->value,
            'updated_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('announcement_targets', [
            'announcement_id' => $announcement->id,
            'target_type' => AnnouncementTargetType::Role->value,
            'target_value' => UserRole::Teacher->value,
        ]);
        $this->assertDatabaseHas('operation_logs', [
            'action' => 'publish_announcement',
            'target_id' => $announcement->id,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.announcements.destroy', $announcement))
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertSoftDeleted('announcements', [
            'id' => $announcement->id,
        ]);
        $this->assertDatabaseHas('operation_logs', [
            'action' => 'delete_announcement',
            'target_id' => $announcement->id,
        ]);
    }

    public function test_teacher_cannot_manage_announcements(): void
    {
        $teacher = Teacher::factory()->create();
        $announcement = Announcement::factory()->create();

        $this->actingAs($teacher->user)
            ->get(route('admin.announcements.index'))
            ->assertForbidden();
        $this->actingAs($teacher->user)
            ->get(route('admin.announcements.create'))
            ->assertForbidden();
        $this->actingAs($teacher->user)
            ->get(route('admin.announcements.edit', $announcement))
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
