<?php

namespace Database\Factories;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 最終評価モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<FinalEvaluation>
 */
final class FinalEvaluationFactory extends Factory
{
    /**
     * 最終評価の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        $submissionScore = fake()->randomFloat(2, 0, 100);
        $attendanceScore = fake()->randomFloat(2, 0, 100);
        $attitudeScore = fake()->randomFloat(2, 0, 100);
        $totalScore = round(
            ($submissionScore + $attendanceScore + $attitudeScore) / 3,
            2,
        );

        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'academic_year' => 2026,
            'term_name' => EvaluationTerm::Annual,
            'submission_score' => $submissionScore,
            'attendance_score' => $attendanceScore,
            'attitude_score' => $attitudeScore,
            'total_score' => $totalScore,
            'grade_level' => null,
            'evaluated_by' => User::factory(),
            'status' => EvaluationStatus::Draft,
        ];
    }
}
