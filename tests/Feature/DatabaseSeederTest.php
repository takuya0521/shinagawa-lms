<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_skips_initial_admin_when_configuration_is_missing(): void
    {
        config([
            'lms.initial_admin.name' => '',
            'lms.initial_admin.email' => '',
            'lms.initial_admin.password' => '',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('class_groups', 2);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_database_seeder_creates_initial_admin_when_configuration_is_complete(): void
    {
        config([
            'lms.initial_admin.name' => '初期管理者',
            'lms.initial_admin.email' => 'initial-admin@example.com',
            'lms.initial_admin.password' => 'InitialPassword123',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'name' => '初期管理者',
            'email' => 'initial-admin@example.com',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);
    }
}
