<?php

namespace Database\Factories;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
final class CourseFactory extends Factory
{
    /**
     * 授業の初期値を返す。
     *
     * @return array<string, mixed>
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
