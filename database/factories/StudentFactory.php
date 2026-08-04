<?php

namespace Database\Factories;

use App\Enums\Grade;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 生徒モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<Student>
 */
final class StudentFactory extends Factory
{
    /**
     * 生徒の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
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
            'grade' => fake()->randomElement(Grade::cases()),
            'affiliation' => null,
            'partner_school' => null,
            'class_group_id' => ClassGroup::factory(),
            'status' => StudentStatus::Active,
        ];
    }
}
