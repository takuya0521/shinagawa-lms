<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
final class StudentFactory extends Factory
{
    /**
     * 生徒基本情報の初期値を返す。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => UserRole::Student,
                'status' => UserStatus::Active,
            ]),
            'student_no' => fake()->unique()->numerify('STU-####'),
            'student_name' => fake()->name(),
            'grade' => fake()->randomElement([
                '1',
                '2',
                '3',
            ]),
            'affiliation' => null,
            'partner_school' => null,
            'class_group_id' => ClassGroup::factory(),
            'status' => StudentStatus::Active,
        ];
    }
}
