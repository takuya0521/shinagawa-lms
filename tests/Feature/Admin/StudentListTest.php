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

final class StudentListTest extends TestCase
{
    use RefreshDatabase;

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

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
