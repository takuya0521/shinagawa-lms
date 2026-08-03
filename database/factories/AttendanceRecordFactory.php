<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
final class AttendanceRecordFactory extends Factory
{
    /**
     * 出欠記録の初期値を返す。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_session_id' => LessonSession::factory(),
            'student_id' => Student::factory(),
            'attendance_status' => fake()->randomElement(
                AttendanceStatus::cases(),
            ),
            'recorded_by' => User::factory(),
            'corrected_by' => null,
            'note' => fake()->optional()->sentence(),
        ];
    }
}
