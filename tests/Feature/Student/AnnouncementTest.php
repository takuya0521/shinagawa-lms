<?php

namespace Tests\Feature\Student;

use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 生徒向けお知らせ閲覧機能を確認するフィーチャーテスト。
 *
 * 生徒のロール・学年・クラス等に一致するお知らせだけが表示され、プロフィール欠損時は閲覧できないことを検証する。
 */
final class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 生徒に一致する公開対象のお知らせだけが表示されることを確認する。
     *
     * 前提: クラス、生徒など、検証に必要なテストデータを準備する。
     * 処理: `student.announcements.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_student_sees_matching_announcements_only(): void
    {
        $classGroup = ClassGroup::factory()->create();
        $otherClassGroup = ClassGroup::factory()->create();
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);

        $visibleTitles = [
            '全員向け',
            '生徒向け',
            '1年向け',
            '所属クラス向け',
        ];

        $this->announcement('全員向け', AnnouncementTargetType::All, null);
        $this->announcement('生徒向け', AnnouncementTargetType::Role, UserRole::Student->value);
        $this->announcement('1年向け', AnnouncementTargetType::Grade, Grade::First->value);
        $this->announcement('所属クラス向け', AnnouncementTargetType::ClassGroup, (string) $classGroup->id);
        $this->announcement('教員向け', AnnouncementTargetType::Role, UserRole::Teacher->value);
        $this->announcement('2年向け', AnnouncementTargetType::Grade, Grade::Second->value);
        $this->announcement('別クラス向け', AnnouncementTargetType::ClassGroup, (string) $otherClassGroup->id);
        $this->announcement('下書き', AnnouncementTargetType::All, null, AnnouncementStatus::Draft);

        $response = $this
            ->actingAs($student->user)
            ->get(route('student.announcements.index'))
            ->assertOk();

        foreach ($visibleTitles as $title) {
            $response->assertSeeText($title);
        }

        $response
            ->assertDontSeeText('教員向け')
            ->assertDontSeeText('2年向け')
            ->assertDontSeeText('別クラス向け')
            ->assertDontSeeText('下書き');
    }

    /**
     * 生徒が自身に公開されたお知らせ詳細を閲覧できることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `student.announcements.show`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_student_can_open_matching_announcement_detail(): void
    {
        $student = Student::factory()->create();
        $announcement = $this->announcement(
            '詳細確認対象',
            AnnouncementTargetType::All,
            null,
        );

        $this
            ->actingAs($student->user)
            ->get(route('student.announcements.show', $announcement))
            ->assertOk()
            ->assertSeeText('詳細確認対象')
            ->assertSeeText($announcement->body);
    }

    /**
     * 生徒プロフィールがないユーザーはお知らせを閲覧できないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `student.announcements.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_student_without_profile_cannot_view_announcements(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
        ]);

        $this
            ->actingAs($user)
            ->get(route('student.announcements.index'))
            ->assertOk()
            ->assertDontSeeText('全員向け');
    }

    /**
     * 指定した公開対象を持つテスト用お知らせを作成して返す。
     */
    private function announcement(
        string $title,
        AnnouncementTargetType $targetType,
        ?string $targetValue,
        AnnouncementStatus $status = AnnouncementStatus::Published,
    ): Announcement {
        $announcement = Announcement::factory()->create([
            'title' => $title,
            'status' => $status,
            'publish_start_at' => now()->subHour(),
            'publish_end_at' => now()->addDay(),
        ]);
        $announcement->targets()->create([
            'target_type' => $targetType,
            'target_value' => $targetValue,
        ]);

        return $announcement;
    }
}
