<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
final class TeacherFactory extends Factory
{
    /**
     * 教員情報の初期値を返す。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => UserRole::Teacher,
                'status' => UserStatus::Active,
            ]),
            'subject_notes' => fake()->optional()->sentence(),
            'status' => MasterStatus::Active,
        ];
    }
}
