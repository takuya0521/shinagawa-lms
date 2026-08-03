<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TimetableModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_belongs_to_subject_class_group_and_teacher(): void
    {
        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $course = Course::factory()->create([
            'subject_id' => $subject->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertTrue(
            $course->subject->is($subject),
        );

        $this->assertTrue(
            $course->classGroup->is($classGroup),
        );

        $this->assertTrue(
            $course->teacher->is($teacher),
        );
    }

    public function test_subject_class_group_and_teacher_have_courses(): void
    {
        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $course = Course::factory()->create([
            'subject_id' => $subject->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertTrue(
            $subject->courses->contains($course),
        );

        $this->assertTrue(
            $classGroup->courses->contains($course),
        );

        $this->assertTrue(
            $teacher->courses->contains($course),
        );
    }

    public function test_course_grade_is_cast_to_enum(): void
    {
        $course = Course::factory()->create([
            'grade' => Grade::Third,
        ]);

        $this->assertSame(
            Grade::Third,
            $course->grade,
        );
    }

    public function test_course_has_timetable_slots(): void
    {
        $course = Course::factory()->create();

        $slot = TimetableSlot::factory()->create([
            'course_id' => $course->id,
        ]);

        $this->assertTrue(
            $course->timetableSlots->contains($slot),
        );

        $this->assertTrue(
            $slot->course->is($course),
        );
    }

    public function test_timetable_slot_values_are_cast_to_enums(): void
    {
        $slot = TimetableSlot::factory()->create([
            'day_of_week' => DayOfWeek::Wednesday,
            'status' => MasterStatus::Inactive,
        ]);

        $this->assertSame(
            DayOfWeek::Wednesday,
            $slot->day_of_week,
        );

        $this->assertSame(
            MasterStatus::Inactive,
            $slot->status,
        );
    }

    public function test_course_is_soft_deleted(): void
    {
        $course = Course::factory()->create();

        $course->delete();

        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_duplicate_course_cannot_be_created_for_same_target(): void
    {
        $course = Course::factory()->create();

        $this->expectException(
            QueryException::class,
        );

        Course::factory()->create([
            'academic_year' => $course->academic_year,
            'class_group_id' => $course->class_group_id,
            'grade' => $course->grade,
            'subject_id' => $course->subject_id,
            'course_name' => $course->course_name,
        ]);
    }

    public function test_same_course_cannot_have_duplicate_day_and_period(): void
    {
        $course = Course::factory()->create();

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);

        $this->expectException(
            QueryException::class,
        );

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);
    }
}
