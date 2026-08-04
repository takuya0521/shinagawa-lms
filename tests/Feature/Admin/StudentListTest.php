<?php

namespace Tests\Feature\Admin;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け生徒一覧画面を確認するフィーチャーテスト。
 *
 * 一覧表示、キーワード検索、クラス・状態絞り込み、権限制御を検証する。
 */
final class StudentListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が生徒一覧を閲覧できることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれることを確認する。
     */
    public function test_admin_can_view_student_list(): void
    {
        $admin = $this->createAdmin();

        $student = Student::factory()->create([
            'student_name' => '一覧表示対象生徒',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.index'));

        $response
            ->assertOk()
            ->assertSee('一覧表示対象生徒')
            ->assertSee($student->user->email);
    }

    /**
     * 教員が管理者向け生徒一覧を閲覧できないことを確認する。
     *
     * 前提: ユーザーなど、検証に必要なテストデータを準備する。
     * 処理: `admin.students.index`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_view_student_list(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.students.index'));

        $response->assertForbidden();
    }

    /**
     * 管理者が氏名や生徒番号のキーワードで生徒を検索できることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれる、表示対象外の内容がレスポンスに含まれないことを確認する。
     */
    public function test_admin_can_search_student_by_keyword(): void
    {
        $admin = $this->createAdmin();

        Student::factory()->create([
            'student_name' => '検索対象生徒',
            'student_no' => 'STU-1001',
        ]);

        Student::factory()->create([
            'student_name' => '別の生徒',
            'student_no' => 'STU-2001',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.index', [
                'keyword' => 'STU-1001',
            ]));

        $response
            ->assertOk()
            ->assertSee('検索対象生徒')
            ->assertDontSee('別の生徒');
    }

    /**
     * 管理者がクラスで生徒を絞り込めることを確認する。
     *
     * 前提: クラス、生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれる、表示対象外の内容がレスポンスに含まれないことを確認する。
     */
    public function test_admin_can_filter_students_by_class_group(): void
    {
        $admin = $this->createAdmin();

        $morningClass = ClassGroup::factory()->create([
            'class_code' => 'AM-TEST',
            'class_name' => '午前テストクラス',
        ]);

        $afternoonClass = ClassGroup::factory()->create([
            'class_code' => 'PM-TEST',
            'class_name' => '午後テストクラス',
        ]);

        Student::factory()->create([
            'student_name' => '午前クラス生徒',
            'class_group_id' => $morningClass->id,
        ]);

        Student::factory()->create([
            'student_name' => '午後クラス生徒',
            'class_group_id' => $afternoonClass->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.index', [
                'class_group_id' => $morningClass->id,
            ]));

        $response
            ->assertOk()
            ->assertSee('午前クラス生徒')
            ->assertDontSee('午後クラス生徒');
    }

    /**
     * 管理者が在籍状態で生徒を絞り込めることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: `admin.students.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれる、表示対象外の内容がレスポンスに含まれないことを確認する。
     */
    public function test_admin_can_filter_students_by_status(): void
    {
        $admin = $this->createAdmin();

        Student::factory()->create([
            'student_name' => '在籍中生徒',
            'status' => StudentStatus::Active,
        ]);

        Student::factory()->create([
            'student_name' => '卒業済み生徒',
            'status' => StudentStatus::Graduated,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.index', [
                'status' => StudentStatus::Graduated->value,
            ]));

        $response
            ->assertOk()
            ->assertSee('卒業済み生徒')
            ->assertDontSee('在籍中生徒');
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
