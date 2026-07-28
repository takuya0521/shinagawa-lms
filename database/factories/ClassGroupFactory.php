<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassGroup>
 */
final class ClassGroupFactory extends Factory
{
    /**
     * クラスグループの初期値を返す。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_code' => fake()->unique()->bothify('CLASS-###'),
            'class_name' => fake()->unique()->word().'クラス',
            'description' => null,
            'status' => MasterStatus::Active,
        ];
    }
}
