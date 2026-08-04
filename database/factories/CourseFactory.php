<?php

namespace Database\Factories;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 授業モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<Course>
 */
final class CourseFactory extends Factory
{
    /**
     * 授業の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'class_group_id' => ClassGroup::factory(),
            'grade' => fake()->randomElement(Grade::cases()),
            'teacher_id' => null,
            'course_name' => fake()->words(3, true),
            'academic_year' => now()->year,
            'google_classroom_url' => fake()->optional()->url(),
            'google_classroom_id' => fake()
                ->optional()
                ->bothify('CLASSROOM-########'),
            'status' => MasterStatus::Active,
        ];
    }
}
