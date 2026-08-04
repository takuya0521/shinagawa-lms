<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\TimetableSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 時間割枠モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<TimetableSlot>
 */
final class TimetableSlotFactory extends Factory
{
    /**
     * 時間割枠の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        $periodNo = fake()->numberBetween(1, 6);
        $startHour = 8 + $periodNo;

        return [
            'course_id' => Course::factory(),
            'day_of_week' => fake()->randomElement(
                DayOfWeek::cases(),
            ),
            'period_no' => $periodNo,
            'start_time' => sprintf(
                '%02d:00:00',
                $startHour,
            ),
            'end_time' => sprintf(
                '%02d:50:00',
                $startHour,
            ),
            'status' => MasterStatus::Active,
        ];
    }
}
