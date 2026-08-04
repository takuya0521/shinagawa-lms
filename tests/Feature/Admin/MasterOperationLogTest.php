<?php

namespace Tests\Feature\Admin;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\OperationLog;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 各種マスタ操作の監査ログを確認するフィーチャーテスト。
 *
 * ユーザー、クラス、科目、授業、時間割の登録・更新・状態変更が安全に記録されることを検証する。
 */
final class MasterOperationLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ユーザーの登録・更新・状態変更が、パスワードを含めず操作ログへ記録されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.users.store`へPOSTリクエスト、`admin.users.update`へPUTリクエスト、`admin.users.status.update`へPATCHリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、取得値が期待値と一致する、対象条件が偽になることを確認する。
     */
    public function test_user_create_update_and_status_change_are_logged_without_password(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->withServerVariables([
                'REMOTE_ADDR' => '192.0.2.10',
            ])
            ->post(route('admin.users.store'), [
                'name' => '監査対象ユーザー',
                'email' => 'audit-user@example.com',
                'role' => UserRole::Admin->value,
                'status' => UserStatus::Active->value,
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
            ])
            ->assertRedirect();

        $target = User::query()
            ->where('email', 'audit-user@example.com')
            ->firstOrFail();

        $createLog = OperationLog::query()
            ->where('action', 'create_user')
            ->where('target_table', 'users')
            ->where('target_id', $target->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $createLog->user_id);
        $this->assertSame('監査対象ユーザー', $createLog->detail['name']);
        $this->assertSame('192.0.2.10', $createLog->detail['ip_address']);
        $this->assertStringNotContainsString(
            'Password@123',
            json_encode(
                $createLog->detail,
                JSON_THROW_ON_ERROR,
            ),
        );

        $this
            ->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => '監査対象ユーザー更新後',
                'email' => 'audit-user-updated@example.com',
                'role' => UserRole::Admin->value,
                'status' => UserStatus::Active->value,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect();

        $updateLog = OperationLog::query()
            ->where('action', 'update_user')
            ->where('target_id', $target->id)
            ->firstOrFail();

        $this->assertSame(
            '監査対象ユーザー',
            $updateLog->detail['before']['name'],
        );
        $this->assertSame(
            '監査対象ユーザー更新後',
            $updateLog->detail['after']['name'],
        );
        $this->assertFalse($updateLog->detail['password_changed']);

        $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status.update', $target),
                [
                    'status' => UserStatus::Suspended->value,
                ],
            )
            ->assertRedirect();

        $statusLog = OperationLog::query()
            ->where('action', 'change_user_status')
            ->where('target_id', $target->id)
            ->firstOrFail();

        $this->assertSame(
            UserStatus::Active->value,
            $statusLog->detail['before']['status'],
        );
        $this->assertSame(
            UserStatus::Suspended->value,
            $statusLog->detail['after']['status'],
        );
    }

    /**
     * 生徒プロフィールの変更内容がユーザー更新ログへ含まれることを確認する。
     *
     * 前提: クラス、生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、取得値が期待値と一致することを確認する。
     */
    public function test_student_profile_update_is_included_in_user_log(): void
    {
        $admin = $this->admin();
        $classGroup = ClassGroup::factory()->create();

        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'student_name' => '更新前生徒',
            'grade' => Grade::First,
            'status' => StudentStatus::Active,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.students.update', $student), [
                'name' => '更新後アカウント名',
                'email' => 'student-audit@example.com',
                'status' => UserStatus::Active->value,
                'password' => '',
                'password_confirmation' => '',
                'student_no' => 'AUDIT-STUDENT',
                'student_name' => '更新後生徒',
                'grade' => Grade::Second->value,
                'affiliation' => '品川',
                'partner_school' => '提携校A',
                'class_group_id' => $classGroup->id,
                'student_status' => StudentStatus::Active->value,
            ])
            ->assertRedirect();

        $log = OperationLog::query()
            ->where('action', 'update_user')
            ->where('target_id', $student->user_id)
            ->firstOrFail();

        $this->assertSame(
            '更新前生徒',
            $log->detail['before']['student']['student_name'],
        );
        $this->assertSame(
            '更新後生徒',
            $log->detail['after']['student']['student_name'],
        );
        $this->assertSame(
            Grade::Second->value,
            $log->detail['after']['student']['grade'],
        );
    }

    /**
     * 教員プロフィールの変更内容がユーザー更新ログへ含まれることを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.teachers.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、取得値が期待値と一致することを確認する。
     */
    public function test_teacher_profile_update_is_included_in_user_log(): void
    {
        $admin = $this->admin();

        $teacher = Teacher::factory()->create([
            'subject_notes' => '更新前メモ',
            'status' => MasterStatus::Active,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.teachers.update', $teacher), [
                'name' => '更新後教員',
                'email' => 'teacher-audit@example.com',
                'status' => UserStatus::Active->value,
                'password' => '',
                'password_confirmation' => '',
                'subject_notes' => '更新後メモ',
                'teacher_status' => MasterStatus::Inactive->value,
            ])
            ->assertRedirect();

        $log = OperationLog::query()
            ->where('action', 'update_user')
            ->where('target_id', $teacher->user_id)
            ->firstOrFail();

        $this->assertSame(
            '更新前メモ',
            $log->detail['before']['teacher']['subject_notes'],
        );
        $this->assertSame(
            '更新後メモ',
            $log->detail['after']['teacher']['subject_notes'],
        );
        $this->assertSame(
            MasterStatus::Inactive->value,
            $log->detail['after']['teacher']['status'],
        );
    }

    /**
     * クラスの登録・更新が操作ログへ記録されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.class-groups.store`へPOSTリクエスト、`admin.class-groups.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、取得値が期待値と一致することを確認する。
     */
    public function test_class_group_create_and_update_are_logged(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->post(route('admin.class-groups.store'), [
                'class_code' => 'audit-class',
                'class_name' => '監査クラス',
                'description' => '登録時説明',
                'status' => MasterStatus::Active->value,
            ])
            ->assertRedirect();

        $classGroup = ClassGroup::query()
            ->where('class_code', 'AUDIT-CLASS')
            ->firstOrFail();

        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'create_class_group',
            'target_table' => 'class_groups',
            'target_id' => $classGroup->id,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.class-groups.update', $classGroup), [
                'class_code' => 'audit-class-2',
                'class_name' => '監査クラス更新後',
                'description' => '更新時説明',
                'status' => MasterStatus::Inactive->value,
            ])
            ->assertRedirect();

        $log = OperationLog::query()
            ->where('action', 'update_class_group')
            ->where('target_id', $classGroup->id)
            ->firstOrFail();

        $this->assertSame(
            '監査クラス',
            $log->detail['before']['class_name'],
        );
        $this->assertSame(
            '監査クラス更新後',
            $log->detail['after']['class_name'],
        );
    }

    /**
     * 科目と授業の登録・更新が操作ログへ記録されることを確認する。
     *
     * 前提: クラスなど、検証に必要なテストデータを準備する。
     * 処理: `admin.subjects.store`へPOSTリクエスト、`admin.subjects.update`へPUTリクエスト、`admin.courses.store`へPOSTリクエスト、関連する後続リクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、取得値が期待値と一致することを確認する。
     */
    public function test_subject_and_course_create_update_are_logged(): void
    {
        $admin = $this->admin();
        $classGroup = ClassGroup::factory()->create();

        $this
            ->actingAs($admin)
            ->post(route('admin.subjects.store'), [
                'subject_code' => 'audit-subject',
                'subject_name' => '監査科目',
                'status' => MasterStatus::Active->value,
            ])
            ->assertRedirect();

        $subject = Subject::query()
            ->where('subject_code', 'AUDIT-SUBJECT')
            ->firstOrFail();

        $this->assertDatabaseHas('operation_logs', [
            'action' => 'create_subject',
            'target_table' => 'subjects',
            'target_id' => $subject->id,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.subjects.update', $subject), [
                'subject_code' => 'audit-subject-2',
                'subject_name' => '監査科目更新後',
                'status' => MasterStatus::Active->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('operation_logs', [
            'action' => 'update_subject',
            'target_table' => 'subjects',
            'target_id' => $subject->id,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'academic_year' => 2026,
                'grade' => Grade::First->value,
                'class_group_id' => $classGroup->id,
                'subject_id' => $subject->id,
                'course_name' => '監査授業',
                'teacher_id' => null,
                'google_classroom_url' => null,
                'google_classroom_id' => null,
                'status' => MasterStatus::Active->value,
            ])
            ->assertRedirect();

        $course = Course::query()
            ->where('course_name', '監査授業')
            ->firstOrFail();

        $this->assertDatabaseHas('operation_logs', [
            'action' => 'create_course',
            'target_table' => 'courses',
            'target_id' => $course->id,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.courses.update', $course), [
                'academic_year' => 2026,
                'grade' => Grade::First->value,
                'class_group_id' => $classGroup->id,
                'subject_id' => $subject->id,
                'course_name' => '監査授業更新後',
                'teacher_id' => null,
                'google_classroom_url' => null,
                'google_classroom_id' => null,
                'status' => MasterStatus::Active->value,
            ])
            ->assertRedirect();

        $log = OperationLog::query()
            ->where('action', 'update_course')
            ->where('target_id', $course->id)
            ->firstOrFail();

        $this->assertSame(
            '監査授業',
            $log->detail['before']['course_name'],
        );
        $this->assertSame(
            '監査授業更新後',
            $log->detail['after']['course_name'],
        );
    }

    /**
     * 時間割枠の登録・更新が操作ログへ記録されることを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.timetable-slots.store`へPOSTリクエスト、`admin.timetable-slots.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、取得値が期待値と一致することを確認する。
     */
    public function test_timetable_slot_create_and_update_are_logged(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();

        $this
            ->actingAs($admin)
            ->post(route('admin.timetable-slots.store'), [
                'course_id' => $course->id,
                'day_of_week' => DayOfWeek::Monday->value,
                'period_no' => 1,
                'start_time' => '09:00',
                'end_time' => '09:50',
                'status' => MasterStatus::Active->value,
            ])
            ->assertRedirect();

        $timetableSlot = TimetableSlot::query()->firstOrFail();

        $this->assertDatabaseHas('operation_logs', [
            'action' => 'create_timetable_slot',
            'target_table' => 'timetable_slots',
            'target_id' => $timetableSlot->id,
        ]);

        $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.timetable-slots.update',
                    $timetableSlot,
                ),
                [
                    'course_id' => $course->id,
                    'day_of_week' => DayOfWeek::Tuesday->value,
                    'period_no' => 2,
                    'start_time' => '10:00',
                    'end_time' => '10:50',
                    'status' => MasterStatus::Active->value,
                ],
            )
            ->assertRedirect();

        $log = OperationLog::query()
            ->where('action', 'update_timetable_slot')
            ->where('target_id', $timetableSlot->id)
            ->firstOrFail();

        $this->assertSame(
            DayOfWeek::Monday->value,
            $log->detail['before']['day_of_week'],
        );
        $this->assertSame(
            DayOfWeek::Tuesday->value,
            $log->detail['after']['day_of_week'],
        );
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
