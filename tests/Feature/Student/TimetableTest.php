<?php

namespace Tests\Feature\Student;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_shows_only_own_target_timetable(): void
    {
        $classGroup = ClassGroup::factory()->create();
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $teacher = Teacher::factory()->create();

        $ownCourse = Course::factory()->create([
            'academic_year' => now()->year,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'teacher_id' => $teacher->id,
            'course_name' => '本人対象授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $ownCourse->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);

        $otherCourse = Course::factory()->create([
            'academic_year' => now()->year,
            'grade' => Grade::Second,
            'course_name' => '対象外授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $otherCourse->id,
            'day_of_week' => DayOfWeek::Tuesday,
        ]);

        $this
            ->actingAs($student->user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSeeText($student->student_name)
            ->assertSeeText($ownCourse->course_name)
            ->assertDontSeeText($otherCourse->course_name);
    }

    public function test_student_can_select_academic_year_for_weekly_timetable(): void
    {
        $classGroup = ClassGroup::factory()->create();
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);

        $course2026 = Course::factory()->create([
            'academic_year' => 2026,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'course_name' => '2026年度授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $course2026->id,
        ]);

        $course2027 = Course::factory()->create([
            'academic_year' => 2027,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'course_name' => '2027年度授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $course2027->id,
        ]);

        $this
            ->actingAs($student->user)
            ->get(route('student.timetable.index', [
                'academic_year' => 2026,
            ]))
            ->assertOk()
            ->assertSeeText($course2026->course_name)
            ->assertDontSeeText($course2027->course_name);
    }

    public function test_teacher_cannot_access_student_timetable(): void
    {
        $teacher = Teacher::factory()->create();

        $this
            ->actingAs($teacher->user)
            ->get(route('student.timetable.index'))
            ->assertForbidden();
    }
}
