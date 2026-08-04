<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 出欠記録モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<AttendanceRecord>
 */
final class AttendanceRecordFactory extends Factory
{
    /**
     * 出欠記録の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
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
