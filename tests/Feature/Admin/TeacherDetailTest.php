<?php

namespace Tests\Feature\Admin;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け教員詳細画面を確認するフィーチャーテスト。
 *
 * 基本情報、担当授業・対象生徒、権限制御、存在しない教員への404応答を検証する。
 */
final class TeacherDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が教員詳細を閲覧できることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.show`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_teacher_detail(): void
    {
        $admin = $this->createAdmin();

        $teacher = Teacher::factory()->create([
            'subject_notes' => '国語と小論文を担当',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.teachers.show', $teacher));

        $response
            ->assertOk()
            ->assertSeeText('教員詳細')
            ->assertSeeText($teacher->user->name)
            ->assertSeeText($teacher->user->email)
            ->assertSeeText('国語と小論文を担当')
            ->assertSeeText('有効');
    }

    /**
     * 教員詳細に担当授業と対象生徒が表示されることを確認する。
     *
     * 前提: 教員、クラス、授業、時間割枠、生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.show`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_teacher_detail_shows_assigned_courses_and_target_students(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'course_name' => '担当授業A',
            'academic_year' => 2026,
        ]);

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 2,
        ]);

        $targetStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '対象生徒A',
        ]);

        Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::Second,
            'student_name' => '対象外生徒B',
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.teachers.show',
                    $teacher,
                ),
            )
            ->assertOk()
            ->assertSeeText('担当授業・対象生徒')
            ->assertSeeText($course->course_name)
            ->assertSeeText('月曜 2時限')
            ->assertSeeText($targetStudent->student_name)
            ->assertDontSeeText('対象外生徒B')
            ->assertSeeText('担当教員設定を開く');
    }

    /**
     * 教員が管理者向け教員詳細を閲覧できないことを確認する。
     *
     * 前提: ユーザー、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.show`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_view_admin_teacher_detail(): void
    {
        $teacherUser = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $teacher = Teacher::factory()->create();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.teachers.show', $teacher));

        $response->assertForbidden();
    }

    /**
     * 存在しない教員の詳細を要求した場合に404を返すことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象なしとしてHTTP 404を返すことを確認する。
     */
    public function test_missing_teacher_returns_not_found(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/teachers/999999');

        $response->assertNotFound();
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
}
