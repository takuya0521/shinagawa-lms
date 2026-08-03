<?php

namespace Database\Factories;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Models\ExternalLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExternalLink> */
final class ExternalLinkFactory extends Factory
{
    protected $model = ExternalLink::class;

    /** @return array<string, mixed> */
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
