<?php

namespace Database\Factories;

use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationLog>
 */
final class OperationLogFactory extends Factory
{
    /**
     * 操作ログの初期値を返す。
     *
     * @return array<string, mixed>
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
