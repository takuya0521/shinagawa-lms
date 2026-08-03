<?php

namespace Database\Factories;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
final class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /** @return array<string, mixed> */
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
