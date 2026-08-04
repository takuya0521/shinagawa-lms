<?php

namespace Tests\Feature;

use App\Enums\AnnouncementTargetType;
use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\EvaluationStatus;
use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\ExternalLink;
use App\Models\FinalEvaluation;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 各ロールのダッシュボード表示内容を確認するフィーチャーテスト。
 *
 * 当日情報、重要なお知らせ、担当授業、外部リンク、集計情報がロール別に表示されることを検証する。
 */
final class DashboardCompletionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストで変更した固定日時などのグローバル状態を元に戻す。
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 管理者ダッシュボードに当日の指標と重要なお知らせが表示されることを確認する。
     *
     * 前提: ユーザー、時間割枠、お知らせなど、検証に必要なテストデータを準備する。
     * 処理: `admin.dashboard`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_dashboard_shows_today_metrics_and_important_announcement(): void
    {
        Carbon::setTestNow('2026-07-27 09:00:00');
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
        [$course] = $this->courseContext();
        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);
        $announcement = Announcement::factory()->create([
            'title' => '重要な学校連絡',
            'is_important' => true,
        ]);
        $announcement->targets()->create([
            'target_type' => AnnouncementTargetType::All,
            'target_value' => null,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('本日の授業')
            ->assertSeeText('出欠未登録')
            ->assertSeeText('未確定評価')
            ->assertSeeText($course->course_name)
            ->assertSeeText($announcement->title);
    }

    /**
     * 教員ダッシュボードに担当授業と閲覧可能なお知らせが表示されることを確認する。
     *
     * 前提: 時間割枠、お知らせなど、検証に必要なテストデータを準備する。
     * 処理: `teacher.dashboard`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_teacher_dashboard_shows_assigned_lesson_and_visible_announcement(): void
    {
        Carbon::setTestNow('2026-07-27 09:00:00');
        [$course, $teacher] = $this->courseContext();
        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 2,
        ]);
        $announcement = Announcement::factory()->create([
            'title' => '教員向け連絡',
        ]);
        $announcement->targets()->create([
            'target_type' => AnnouncementTargetType::Role,
            'target_value' => UserRole::Teacher->value,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSeeText($teacher->user->name.'先生')
            ->assertSeeText($course->course_name)
            ->assertSeeText('本日の出欠未登録')
            ->assertSeeText('評価未確定')
            ->assertSeeText($announcement->title);
    }

    /**
     * 生徒ダッシュボードにプロフィール、お知らせ、外部リンク、各種集計が表示されることを確認する。
     *
     * 前提: 時間割枠、お知らせ、外部リンク、最終評価、授業実施など、検証に必要なテストデータを準備する。
     * 処理: `student.dashboard`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_student_dashboard_shows_profile_announcements_links_and_summaries(): void
    {
        Carbon::setTestNow('2026-07-27 09:00:00');
        [$course, , $student] = $this->courseContext();
        $timetableSlot = TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 3,
        ]);
        $announcement = Announcement::factory()->create([
            'title' => '生徒向け最新連絡',
        ]);
        $announcement->targets()->create([
            'target_type' => AnnouncementTargetType::Role,
            'target_value' => UserRole::Student->value,
        ]);
        $link = ExternalLink::factory()->create([
            'link_type' => ExternalLinkType::Drive,
            'link_name' => '共有資料Drive',
            'scope_type' => ExternalLinkScopeType::Student,
            'scope_id' => $student->id,
        ]);

        FinalEvaluation::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => 2026,
            'submission_score' => 86,
            'attendance_score' => 92,
            'attitude_score' => 75,
            'total_score' => 84.3,
            'grade_level' => 4,
            'status' => EvaluationStatus::Confirmed,
        ]);

        foreach ([
            AttendanceStatus::Present,
            AttendanceStatus::Late,
            AttendanceStatus::Absent,
        ] as $index => $attendanceStatus) {
            $lessonSession = LessonSession::factory()->create([
                'timetable_slot_id' => $timetableSlot->id,
                'lesson_date' => now()->subDays($index),
            ]);
            AttendanceRecord::factory()->create([
                'lesson_session_id' => $lessonSession->id,
                'student_id' => $student->id,
                'attendance_status' => $attendanceStatus,
            ]);
        }

        $this
            ->actingAs($student->user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSeeText($student->student_name.'さん')
            ->assertSeeText('Google Classroom')
            ->assertSeeText($announcement->title)
            ->assertSeeText($link->link_name)
            ->assertSeeText('年間行事カレンダー')
            ->assertSeeText('面談希望フォーム')
            ->assertSeeText('84.3点')
            ->assertSeeText('67%');
    }

    /**
     * ダッシュボードテストで使用する授業、時間割、担当教員、生徒を作成する。
     */
    private function courseContext(): array
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'academic_year' => 2026,
            'course_name' => 'ダッシュボード確認授業',
        ]);
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '確認生徒',
        ]);

        return [$course, $teacher, $student];
    }
}
