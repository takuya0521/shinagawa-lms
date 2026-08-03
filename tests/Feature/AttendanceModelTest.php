<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttendanceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_session_relationships_and_casts_are_available(): void
    {
        $slot = TimetableSlot::factory()->create();
        $creator = User::factory()->create();

        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'created_by' => $creator->id,
            'lesson_date' => '2026-07-27',
            'status' => LessonStatus::Completed,
        ]);

        $this->assertTrue($lessonSession->timetableSlot->is($slot));
        $this->assertTrue($lessonSession->creator->is($creator));
        $this->assertSame('2026-07-27', $lessonSession->lesson_date->format('Y-m-d'));
        $this->assertSame(LessonStatus::Completed, $lessonSession->status);
    }

    public function test_attendance_record_relationships_and_casts_are_available(): void
    {
        $lessonSession = LessonSession::factory()->create();
        $student = Student::factory()->create();
        $recorder = User::factory()->create();
        $corrector = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $student->id,
            'recorded_by' => $recorder->id,
            'corrected_by' => $corrector->id,
            'attendance_status' => AttendanceStatus::Late,
        ]);

        $this->assertTrue($record->lessonSession->is($lessonSession));
        $this->assertTrue($record->student->is($student));
        $this->assertTrue($record->recorder->is($recorder));
        $this->assertTrue($record->corrector->is($corrector));
        $this->assertSame(AttendanceStatus::Late, $record->attendance_status);
    }

    public function test_same_slot_and_date_cannot_create_duplicate_lesson_session(): void
    {
        $lessonSession = LessonSession::factory()->create([
            'lesson_date' => '2026-07-27',
        ]);

        $this->expectException(QueryException::class);

        LessonSession::factory()->create([
            'timetable_slot_id' => $lessonSession->timetable_slot_id,
            'lesson_date' => '2026-07-27',
        ]);
    }

    public function test_same_session_and_student_cannot_create_duplicate_attendance_record(): void
    {
        $record = AttendanceRecord::factory()->create();

        $this->expectException(QueryException::class);

        AttendanceRecord::factory()->create([
            'lesson_session_id' => $record->lesson_session_id,
            'student_id' => $record->student_id,
        ]);
    }
}
