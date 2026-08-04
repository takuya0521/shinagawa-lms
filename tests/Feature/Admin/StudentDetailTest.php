<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け生徒詳細画面を確認するフィーチャーテスト。
 *
 * 正常表示、権限制御、存在しない生徒への404応答を検証する。
 */
final class StudentDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が生徒詳細を閲覧できることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.show`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_student_detail(): void
    {
        $admin = $this->createAdmin();

        $student = Student::factory()->create([
            'student_no' => 'STU-DETAIL-001',
            'student_name' => '詳細確認生徒',
            'grade' => '2',
            'affiliation' => '品川高等学院',
            'partner_school' => '提携校テスト',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.show', $student));

        $response
            ->assertOk()
            ->assertSeeText('生徒詳細')
            ->assertSeeText('STU-DETAIL-001')
            ->assertSeeText('詳細確認生徒')
            ->assertSeeText('品川高等学院')
            ->assertSeeText('提携校テスト')
            ->assertSeeText($student->user->email)
            ->assertSeeText($student->classGroup->class_name);
    }

    /**
     * 教員が管理者向け生徒詳細を閲覧できないことを確認する。
     *
     * 前提: ユーザー、生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.show`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_view_student_detail(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $student = Student::factory()->create();

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.students.show', $student));

        $response->assertForbidden();
    }

    /**
     * 存在しない生徒の詳細を要求した場合に404を返すことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象なしとしてHTTP 404を返すことを確認する。
     */
    public function test_missing_student_returns_not_found(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/students/999999');

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
