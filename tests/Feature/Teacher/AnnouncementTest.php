<?php

namespace Tests\Feature\Teacher;

use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 教員向けお知らせ閲覧機能を確認するフィーチャーテスト。
 *
 * 全体・ロール・担当授業対象のお知らせだけが表示され、無関係なお知らせへアクセスできないことを検証する。
 */
final class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 教員に全体・教員ロール・担当対象のお知らせだけが表示されることを確認する。
     *
     * 前提: 教員、クラス、授業など、検証に必要なテストデータを準備する。
     * 処理: `teacher.announcements.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_teacher_sees_all_role_and_assigned_target_announcements_only(): void
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::Second,
        ]);

        $visibleTitles = [
            '全員向け',
            '教員向け',
            '担当学年向け',
            '担当クラス向け',
        ];

        $this->announcement('全員向け', AnnouncementTargetType::All, null);
        $this->announcement('教員向け', AnnouncementTargetType::Role, UserRole::Teacher->value);
        $this->announcement('担当学年向け', AnnouncementTargetType::Grade, Grade::Second->value);
        $this->announcement('担当クラス向け', AnnouncementTargetType::ClassGroup, (string) $classGroup->id);
        $this->announcement('生徒向け', AnnouncementTargetType::Role, UserRole::Student->value);
        $this->announcement('別学年向け', AnnouncementTargetType::Grade, Grade::Third->value);
        $this->announcement('下書き', AnnouncementTargetType::All, null, AnnouncementStatus::Draft);
        $this->announcement(
            '掲載終了',
            AnnouncementTargetType::All,
            null,
            AnnouncementStatus::Published,
            now()->subDays(2),
            now()->subDay(),
        );

        $response = $this
            ->actingAs($teacher->user)
            ->get(route('teacher.announcements.index'))
            ->assertOk();

        foreach ($visibleTitles as $title) {
            $response->assertSeeText($title);
        }

        $response
            ->assertDontSeeText('生徒向け')
            ->assertDontSeeText('別学年向け')
            ->assertDontSeeText('下書き')
            ->assertDontSeeText('掲載終了');
    }

    /**
     * 教員が自身と無関係なお知らせ詳細を閲覧できないことを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `teacher.announcements.show`へGETリクエストを送信する。
     * 期待結果: 対象なしとしてHTTP 404を返すことを確認する。
     */
    public function test_teacher_cannot_open_unrelated_announcement(): void
    {
        $teacher = Teacher::factory()->create();
        $announcement = $this->announcement(
            '生徒専用',
            AnnouncementTargetType::Role,
            UserRole::Student->value,
        );

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.announcements.show', $announcement))
            ->assertNotFound();
    }

    /**
     * 指定した公開対象を持つテスト用お知らせを作成して返す。
     */
    private function announcement(
        string $title,
        AnnouncementTargetType $targetType,
        ?string $targetValue,
        AnnouncementStatus $status = AnnouncementStatus::Published,
        ?\DateTimeInterface $publishStartAt = null,
        ?\DateTimeInterface $publishEndAt = null,
    ): Announcement {
        $announcement = Announcement::factory()->create([
            'title' => $title,
            'status' => $status,
            'publish_start_at' => $publishStartAt ?? now()->subHour(),
            'publish_end_at' => $publishEndAt ?? now()->addDay(),
        ]);
        $announcement->targets()->create([
            'target_type' => $targetType,
            'target_value' => $targetValue,
        ]);

        return $announcement;
    }
}
