<?php

namespace Database\Factories;

use App\Enums\AnnouncementTargetType;
use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AnnouncementTarget> */
final class AnnouncementTargetFactory extends Factory
{
    protected $model = AnnouncementTarget::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'announcement_id' => Announcement::factory(),
            'target_type' => AnnouncementTargetType::All,
            'target_value' => null,
        ];
    }
}
