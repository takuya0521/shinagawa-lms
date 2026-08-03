<?php

namespace Tests\Feature;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AnnouncementModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_relations_and_casts_are_available(): void
    {
        $creator = User::factory()->create();
        $updater = User::factory()->create();
        $announcement = Announcement::factory()->create([
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
            'notice_type' => AnnouncementNoticeType::Office,
            'status' => AnnouncementStatus::Published,
            'is_important' => true,
        ]);
        $target = AnnouncementTarget::factory()->create([
            'announcement_id' => $announcement->id,
            'target_type' => AnnouncementTargetType::Role,
            'target_value' => 'student',
        ]);

        $this->assertTrue($announcement->creator->is($creator));
        $this->assertTrue($announcement->updater->is($updater));
        $this->assertTrue($announcement->targets->contains($target));
        $this->assertSame(AnnouncementNoticeType::Office, $announcement->notice_type);
        $this->assertSame(AnnouncementStatus::Published, $announcement->status);
        $this->assertTrue($announcement->is_important);
        $this->assertSame(AnnouncementTargetType::Role, $target->target_type);
    }

    public function test_published_scope_excludes_draft_future_and_expired_records(): void
    {
        $visible = Announcement::factory()->create([
            'title' => '公開中',
            'status' => AnnouncementStatus::Published,
            'publish_start_at' => now()->subHour(),
            'publish_end_at' => now()->addHour(),
        ]);
        Announcement::factory()->create([
            'title' => '下書き',
            'status' => AnnouncementStatus::Draft,
        ]);
        Announcement::factory()->create([
            'title' => '未来',
            'publish_start_at' => now()->addDay(),
        ]);
        Announcement::factory()->create([
            'title' => '掲載終了',
            'publish_end_at' => now()->subDay(),
        ]);

        $results = Announcement::query()->publishedAt(now())->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->firstOrFail()->is($visible));
    }

    public function test_announcement_is_soft_deleted(): void
    {
        $announcement = Announcement::factory()->create();

        $announcement->delete();

        $this->assertSoftDeleted('announcements', [
            'id' => $announcement->id,
        ]);
    }
}
