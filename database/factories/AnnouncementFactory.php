<?php

namespace Database\Factories;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * お知らせモデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<Announcement>
 */
final class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * お知らせの標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
            'notice_type' => AnnouncementNoticeType::School,
            'is_important' => false,
            'publish_start_at' => now()->subDay(),
            'publish_end_at' => now()->addMonth(),
            'status' => AnnouncementStatus::Published,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
