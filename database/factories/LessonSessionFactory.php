<?php

namespace Database\Factories;

use App\Enums\LessonStatus;
use App\Models\LessonSession;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonSession>
 */
final class LessonSessionFactory extends Factory
{
    /**
     * 授業実施日の初期値を返す。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timetable_slot_id' => TimetableSlot::factory(),
            'lesson_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'status' => LessonStatus::Scheduled,
            'created_by' => User::factory(),
        ];
    }
}
