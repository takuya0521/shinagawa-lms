<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
final class SubjectFactory extends Factory
{
    /**
     * 科目の初期値を返す。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_code' => strtoupper(
                fake()->unique()->bothify('SUB-###??'),
            ),
            'subject_name' => fake()->unique()->words(
                2,
                true,
            ),
            'status' => MasterStatus::Active,
        ];
    }
}
