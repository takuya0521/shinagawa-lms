<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_attendance_summary(): void
    {
        [$lessonSession, $student, $teacher] = $this->attendanceRecordContext();

        AttendanceRecord::factory()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $student->id,
            'recorded_by' => $teacher->user_id,
            'attendance_status' => AttendanceStatus::Present,
        ]);

        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->get(route('admin.attendance.index', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSeeText($student->student_name)
            ->assertSeeText(AttendanceStatus::Present->label());
    }

    public function test_admin_can_open_daily_attendance_correction(): void
    {
        [$lessonSession, $student] = $this->attendanceRecordContext();
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->get(route('admin.attendance.edit', [
                'lesson_session_id' => $lessonSession->id,
            ]))
            ->assertOk()
            ->assertSeeText($student->student_name)
            ->assertSeeText('日別出欠確認・修正');
    }

    public function test_admin_correction_sets_corrected_by(): void
    {
        [$lessonSession, $student, $teacher] = $this->attendanceRecordContext();
        $admin = $this->admin();

        AttendanceRecord::factory()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $student->id,
            'recorded_by' => $teacher->user_id,
            'attendance_status' => AttendanceStatus::Present,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.attendance.update', $lessonSession), [
                'records' => [[
                    'student_id' => $student->id,
                    'attendance_status' => AttendanceStatus::Absent->value,
                    'note' => '管理者確認済み',
                ]],
            ])
            ->assertRedirect(route('admin.attendance.edit', [
                'lesson_session_id' => $lessonSession->id,
            ]));

        $this->assertDatabaseHas('attendance_records', [
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $student->id,
            'attendance_status' => AttendanceStatus::Absent->value,
            'corrected_by' => $admin->id,
            'note' => '管理者確認済み',
        ]);
    }

    public function test_admin_can_view_student_attendance_detail(): void
    {
        [$lessonSession, $student, $teacher] = $this->attendanceRecordContext();
        AttendanceRecord::factory()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $student->id,
            'recorded_by' => $teacher->user_id,
            'attendance_status' => AttendanceStatus::Late,
        ]);

        $this
            ->actingAs($this->admin())
            ->get(route('admin.students.attendance.show', [
                'student' => $student,
                'date_from' => '2026-04-01',
                'date_to' => '2027-03-31',
            ]))
            ->assertOk()
            ->assertSeeText($student->student_name)
            ->assertSeeText(AttendanceStatus::Late->label());
    }

    public function test_teacher_cannot_access_admin_attendance_pages(): void
    {
        $teacher = Teacher::factory()->create();
        [$lessonSession, $student] = $this->attendanceRecordContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.attendance.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.attendance.edit', [
                'lesson_session_id' => $lessonSession->id,
            ]))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.students.attendance.show', $student))
            ->assertForbidden();
    }

    /**
     * @return array{0: LessonSession, 1: Student, 2: Teacher}
     */
    private function attendanceRecordContext(): array
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
        ]);
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'created_by' => $teacher->user_id,
        ]);

        return [$lessonSession, $student, $teacher];
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
