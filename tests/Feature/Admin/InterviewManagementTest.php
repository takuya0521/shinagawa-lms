<?php

namespace Tests\Feature\Admin;

use App\Enums\Grade;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\InterviewRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け面談記録管理機能を確認するフィーチャーテスト。
 *
 * 一覧、登録・更新、検索条件、URL検証、操作ログ、生徒詳細連携、権限制御を検証する。
 */
final class InterviewManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が面談記録一覧を閲覧できることを確認する。
     *
     * 前提: 面談記録など、検証に必要なテストデータを準備する。
     * 処理: `admin.interviews.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_interview_list(): void
    {
        $admin = $this->admin();
        $interviewRecord = InterviewRecord::factory()->create([
            'interview_date' => now()->format('Y-m-d'),
            'interview_type' => '進路面談',
            'next_action' => '志望校を次回確認する',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.interviews.index'))
            ->assertOk()
            ->assertSeeText($interviewRecord->student->student_name)
            ->assertSeeText('進路面談')
            ->assertSeeText('志望校を次回確認する');
    }

    /**
     * 管理者が面談記録を登録し、操作ログが記録されることを確認する。
     *
     * 前提: 生徒、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.interviews.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、対象条件が真になることを確認する。
     */
    public function test_admin_can_create_interview_and_operation_log_is_created(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.interviews.store'), [
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'interview_date' => '2026-07-29',
                'interview_type' => ' 定期面談 ',
                'memo' => ' 学習状況を確認 ',
                'next_action' => ' 次回までに課題を提出 ',
                'drive_url' => 'https://drive.google.com/example',
                'meet_url' => 'https://meet.google.com/example',
            ]);

        $interviewRecord = InterviewRecord::query()->firstOrFail();

        $response
            ->assertRedirect(
                route('admin.interviews.edit', $interviewRecord),
            )
            ->assertSessionHas('success', '面談記録を登録しました。');

        $this->assertDatabaseHas('interview_records', [
            'id' => $interviewRecord->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'interview_type' => '定期面談',
            'memo' => '学習状況を確認',
            'next_action' => '次回までに課題を提出',
            'created_by' => $admin->id,
        ]);

        $this->assertTrue(
            InterviewRecord::query()
                ->whereKey($interviewRecord->id)
                ->whereDate('interview_date', '2026-07-29')
                ->exists(),
        );

        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'create_interview',
            'target_table' => 'interview_records',
            'target_id' => $interviewRecord->id,
        ]);
    }

    /**
     * 管理者が面談記録を更新し、操作ログが記録されることを確認する。
     *
     * 前提: 面談記録など、検証に必要なテストデータを準備する。
     * 処理: `admin.interviews.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_update_interview_and_operation_log_is_created(): void
    {
        $admin = $this->admin();
        $interviewRecord = InterviewRecord::factory()->create([
            'interview_type' => '定期面談',
            'memo' => '更新前',
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route('admin.interviews.update', $interviewRecord),
                [
                    'student_id' => $interviewRecord->student_id,
                    'teacher_id' => $interviewRecord->teacher_id,
                    'interview_date' => '2026-07-30',
                    'interview_type' => '進路面談',
                    'memo' => '更新後',
                    'next_action' => '資料を準備する',
                    'drive_url' => null,
                    'meet_url' => null,
                ],
            );

        $response
            ->assertRedirect(
                route('admin.interviews.edit', $interviewRecord),
            )
            ->assertSessionHas('success', '面談記録を更新しました。');

        $this->assertDatabaseHas('interview_records', [
            'id' => $interviewRecord->id,
            'interview_type' => '進路面談',
            'memo' => '更新後',
            'updated_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'update_interview',
            'target_table' => 'interview_records',
            'target_id' => $interviewRecord->id,
        ]);
    }

    /**
     * 管理者が生徒と面談種別で面談記録を絞り込めることを確認する。
     *
     * 前提: 生徒、面談記録など、検証に必要なテストデータを準備する。
     * 処理: `admin.interviews.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれる、表示対象外の内容がレスポンスに含まれないことを確認する。
     */
    public function test_admin_can_filter_interviews_by_student_and_type(): void
    {
        $admin = $this->admin();
        $targetStudent = Student::factory()->create([
            'student_name' => '対象生徒',
        ]);
        $otherStudent = Student::factory()->create([
            'student_name' => '対象外生徒',
        ]);
        $target = InterviewRecord::factory()->create([
            'student_id' => $targetStudent->id,
            'interview_date' => now()->format('Y-m-d'),
            'interview_type' => '進路面談',
        ]);
        $other = InterviewRecord::factory()->create([
            'student_id' => $otherStudent->id,
            'interview_date' => now()->format('Y-m-d'),
            'interview_type' => '定期面談',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.interviews.index', [
                'student_id' => $targetStudent->id,
                'interview_type' => '進路',
            ]))
            ->assertOk()
            ->assertSee(
                '<p class="font-semibold text-slate-900">'
                    .$target->student->student_name
                    .'</p>',
                false,
            )
            ->assertDontSee(
                '<p class="font-semibold text-slate-900">'
                    .$other->student->student_name
                    .'</p>',
                false,
            );
    }

    /**
     * 面談記録へHTTPS以外の関連リンクを登録できないことを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.interviews.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_non_https_links_cannot_be_registered(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();

        $this
            ->actingAs($admin)
            ->from(route('admin.interviews.create'))
            ->post(route('admin.interviews.store'), [
                'student_id' => $student->id,
                'teacher_id' => null,
                'interview_date' => '2026-07-29',
                'interview_type' => null,
                'memo' => null,
                'next_action' => null,
                'drive_url' => 'http://drive.google.com/example',
                'meet_url' => null,
            ])
            ->assertRedirect(route('admin.interviews.create'))
            ->assertSessionHasErrors('drive_url');
    }

    /**
     * 教員が管理者向け面談管理画面へアクセスできないことを確認する。
     *
     * 前提: 教員、面談記録など、検証に必要なテストデータを準備する。
     * 処理: `admin.interviews.index`へGETリクエスト、`admin.interviews.create`へGETリクエスト、`admin.interviews.edit`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_access_admin_interview_pages(): void
    {
        $teacher = Teacher::factory()->create();
        $interviewRecord = InterviewRecord::factory()->create();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.interviews.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.interviews.create'))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.interviews.edit', $interviewRecord))
            ->assertForbidden();
    }

    /**
     * 生徒詳細画面に最近の面談履歴が表示されることを確認する。
     *
     * 前提: クラス、生徒、面談記録など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.show`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_student_detail_shows_recent_interview_history(): void
    {
        $admin = $this->admin();
        $classGroup = ClassGroup::factory()->create();
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        InterviewRecord::factory()->create([
            'student_id' => $student->id,
            'interview_date' => '2026-07-29',
            'interview_type' => '希望面談',
            'next_action' => '保護者へ連絡する',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSeeText('面談履歴')
            ->assertSeeText('希望面談')
            ->assertSeeText('保護者へ連絡する');
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
