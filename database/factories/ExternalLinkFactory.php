<?php

namespace Database\Factories;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Models\ExternalLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 外部リンクモデルのテストデータを生成するFactory。
 *
 * 各テストで再現性のある標準値を用意し、必要に応じて属性を上書きして使用する。
 *
 * @extends Factory<ExternalLink>
 */
final class ExternalLinkFactory extends Factory
{
    protected $model = ExternalLink::class;

    /**
     * 外部リンクの標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        return [
            'link_type' => ExternalLinkType::Calendar,
            'link_name' => fake()->words(3, true),
            'url' => 'https://calendar.google.com/calendar/u/0/r',
            'scope_type' => ExternalLinkScopeType::Global,
            'scope_id' => null,
            'display_order' => 0,
            'status' => MasterStatus::Active,
        ];
    }
}
