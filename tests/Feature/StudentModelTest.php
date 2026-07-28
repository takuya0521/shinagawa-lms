<?php

namespace Tests\Feature;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentModelTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_student_is_soft_deleted(): void
    {
        $student = Student::factory()->create();

        $student->delete();

        $this->assertSoftDeleted('students', [
            'id' => $student->id,
        ]);
    }

    public function test_one_user_cannot_have_multiple_student_records(): void
    {
        $student = Student::factory()->create();

        $this->expectException(\Throwable::class);

        Student::factory()->create([
            'user_id' => $student->user_id,
        ]);
    }
}
