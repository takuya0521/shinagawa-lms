<?php

namespace Tests\Feature\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_generate_session_and_view_target_students(): void
    {
        [$teacher, $slot, $students] = $this->attendanceContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.attendance.edit', [
                'timetable_slot_id' => $slot->id,
                'lesson_date' => '2026-07-27',
            ]))
            ->assertOk()
            ->assertSeeText($students[0]->student_name)
            ->assertSeeText($students[1]->student_name);

        $lessonSessionExists = LessonSession::query()
            ->where(
                'timetable_slot_id',
                $slot->id,
            )
            ->whereDate(
                'lesson_date',
                '2026-07-27',
            )
            ->where(
                'status',
                LessonStatus::Scheduled->value,
            )
            ->where(
                'created_by',
                $teacher->user_id,
            )
            ->exists();

        $this->assertTrue(
            $lessonSessionExists,
            '授業実施日が想定どおり作成されていません。',
        );
    }

    public function test_lesson_date_must_match_timetable_weekday(): void
    {
        [$teacher, $slot] = $this->attendanceContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.attendance.edit', [
                'timetable_slot_id' => $slot->id,
                'lesson_date' => '2026-07-28',
            ]))
            ->assertSessionHasErrors('lesson_date');
    }

    public function test_teacher_can_save_all_target_students_attendance(): void
    {
        [$teacher, $slot, $students] = $this->attendanceContext();

        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'created_by' => $teacher->user_id,
            'status' => LessonStatus::Scheduled,
        ]);

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.attendance.update', $lessonSession), [
                'records' => [
                    [
                        'student_id' => $students[0]->id,
                        'attendance_status' => AttendanceStatus::Present->value,
                        'note' => null,
                    ],
                    [
                        'student_id' => $students[1]->id,
                        'attendance_status' => AttendanceStatus::Late->value,
                        'note' => '電車遅延',
                    ],
                ],
            ])
            ->assertRedirect(route('teacher.attendance.edit', [
                'timetable_slot_id' => $slot->id,
                'lesson_date' => '2026-07-27',
            ]));

        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertDatabaseHas('attendance_records', [
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $students[1]->id,
            'attendance_status' => AttendanceStatus::Late->value,
            'recorded_by' => $teacher->user_id,
            'note' => '電車遅延',
        ]);
        $this->assertSame(
            LessonStatus::Completed,
            $lessonSession->refresh()->status,
        );
    }

    public function test_teacher_cannot_update_another_teachers_session(): void
    {
        $teacher = Teacher::factory()->create();
        [$otherTeacher, $slot, $students] = $this->attendanceContext();

        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'created_by' => $otherTeacher->user_id,
        ]);

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.attendance.update', $lessonSession), [
                'records' => array_map(
                    static fn (Student $student): array => [
                        'student_id' => $student->id,
                        'attendance_status' => AttendanceStatus::Present->value,
                        'note' => null,
                    ],
                    $students,
                ),
            ])
            ->assertForbidden();
    }

    public function test_teacher_attendance_list_shows_student_records(): void
    {
        [$teacher, $slot, $students] = $this->attendanceContext();
        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'created_by' => $teacher->user_id,
            'status' => LessonStatus::Completed,
        ]);

        AttendanceRecord::factory()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $students[0]->id,
            'recorded_by' => $teacher->user_id,
            'attendance_status' => AttendanceStatus::Present,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.attendance.index', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSeeText($students[0]->student_name)
            ->assertSeeText(AttendanceStatus::Present->label());
    }

    /**
     * @return array{0: Teacher, 1: TimetableSlot, 2: list<Student>}
     */
    private function attendanceContext(): array
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $slot = TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);
        $students = [
            Student::factory()->create([
                'class_group_id' => $classGroup->id,
                'grade' => Grade::First,
                'student_name' => '生徒A',
            ]),
            Student::factory()->create([
                'class_group_id' => $classGroup->id,
                'grade' => Grade::First,
                'student_name' => '生徒B',
            ]),
        ];

        return [$teacher, $slot, $students];
    }
}
