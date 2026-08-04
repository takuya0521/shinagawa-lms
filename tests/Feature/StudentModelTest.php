<?php

namespace Tests\Feature;

use App\Enums\Grade;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 生徒モデルの関連、型変換、一意制約、論理削除を確認するテスト。
 *
 * ユーザー・クラスとの関連、Enum変換、ユーザー単位の一意制約、論理削除を検証する。
 */
final class StudentModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 生徒がユーザーとクラスに所属するリレーションを持つことを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致する、取得結果が期待する型になることを確認する。
     */
    public function test_student_belongs_to_user_and_class_group(): void
    {
        $student = Student::factory()->create();

        $this->assertInstanceOf(
            User::class,
            $student->user,
        );

        $this->assertInstanceOf(
            ClassGroup::class,
            $student->classGroup,
        );

        $this->assertSame(
            UserRole::Student,
            $student->user->role,
        );
    }

    /**
     * ユーザーが1件の生徒プロフィールを持つことを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象条件が真になることを確認する。
     */
    public function test_user_has_one_student(): void
    {
        $student = Student::factory()->create();

        $studentFromUser = $student->user
            ->student()
            ->firstOrFail();

        $this->assertTrue(
            $student->is($studentFromUser),
        );
    }

    /**
     * 生徒の学年がGrade Enumへキャストされることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    public function test_student_grade_is_cast_to_enum(): void
    {
        $student = Student::factory()->create([
            'grade' => Grade::Second,
        ]);

        $this->assertSame(
            Grade::Second,
            $student->grade,
        );
    }

    /**
     * 生徒の在籍状態がStudentStatus Enumへキャストされることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    public function test_student_status_is_cast_to_enum(): void
    {
        $student = Student::factory()->create([
            'status' => StudentStatus::Graduated,
        ]);

        $this->assertSame(
            StudentStatus::Graduated,
            $student->status,
        );
    }

    /**
     * 生徒削除時にレコードが論理削除されることを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象レコードが論理削除されることを確認する。
     */
    public function test_student_is_soft_deleted(): void
    {
        $student = Student::factory()->create();

        $student->delete();

        $this->assertSoftDeleted('students', [
            'id' => $student->id,
        ]);
    }

    /**
     * 1ユーザーに複数の生徒プロフィールを登録できないことを確認する。
     *
     * 前提: 生徒など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した例外または制約違反が発生することを確認する。
     */
    public function test_one_user_cannot_have_multiple_student_records(): void
    {
        $student = Student::factory()->create();

        $this->expectException(\Throwable::class);

        Student::factory()->create([
            'user_id' => $student->user_id,
        ]);
    }
}
