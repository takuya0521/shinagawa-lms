<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 教員モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<Teacher>
 */
final class TeacherFactory extends Factory
{
    /**
     * 教員の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
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
