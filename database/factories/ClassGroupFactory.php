<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * クラスモデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<ClassGroup>
 */
final class ClassGroupFactory extends Factory
{
    /**
     * クラスの標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
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
