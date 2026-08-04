<?php

namespace Database\Factories;

use App\Enums\AnnouncementTargetType;
use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * お知らせ公開対象モデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<AnnouncementTarget>
 */
final class AnnouncementTargetFactory extends Factory
{
    protected $model = AnnouncementTarget::class;

    /**
     * お知らせ公開対象の標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        return [
            'announcement_id' => Announcement::factory(),
            'target_type' => AnnouncementTargetType::All,
            'target_value' => null,
        ];
    }
}
