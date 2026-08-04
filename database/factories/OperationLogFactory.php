<?php

namespace Database\Factories;

use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 操作ログモデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<OperationLog>
 */
final class OperationLogFactory extends Factory
{
    /**
     * 操作ログの標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => 'update',
            'target_table' => 'example',
            'target_id' => fake()->numberBetween(1, 1000),
            'detail' => [
                'reason' => fake()->sentence(),
            ],
        ];
    }
}
