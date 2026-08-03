<?php

namespace Database\Factories;

use App\Models\InterviewRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewRecord>
 */
final class InterviewRecordFactory extends Factory
{
    /**
     * 面談記録の初期値を返す。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'interview_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'interview_type' => fake()->randomElement([
                '定期面談',
                '希望面談',
                '進路面談',
            ]),
            'memo' => fake()->paragraph(),
            'next_action' => fake()->optional()->sentence(),
            'drive_url' => fake()->optional()->url(),
            'meet_url' => fake()->optional()->url(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
