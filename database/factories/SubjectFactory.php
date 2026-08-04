<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 科目モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<Subject>
 */
final class SubjectFactory extends Factory
{
    /**
     * 科目の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
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
