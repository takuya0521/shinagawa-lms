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

/**
 * お知らせモデルの関連・スコープ・論理削除を確認するテスト。
 *
 * Eloquentリレーション、型変換、公開期間スコープ、論理削除をデータベース上で検証する。
 */
final class AnnouncementModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * お知らせモデルのリレーションと属性キャストが利用できることを確認する。
     *
     * 前提: ユーザー、お知らせ、お知らせ公開対象など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致する、対象条件が真になることを確認する。
     */
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

    /**
     * 公開中スコープが下書き・公開前・公開終了のお知らせを除外することを確認する。
     *
     * 前提: お知らせなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象条件が真になることを確認する。
     */
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

    /**
     * お知らせ削除時にレコードが論理削除されることを確認する。
     *
     * 前提: お知らせなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象レコードが論理削除されることを確認する。
     */
    public function test_announcement_is_soft_deleted(): void
    {
        $announcement = Announcement::factory()->create();

        $announcement->delete();

        $this->assertSoftDeleted('announcements', [
            'id' => $announcement->id,
        ]);
    }
}
