<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $targetUser = User::factory()->create([
            'name' => '一覧表示対象ユーザー',
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index'));

        $response
            ->assertOk()
            ->assertSee($targetUser->name)
            ->assertSee($targetUser->email);
    }

    public function test_teacher_cannot_view_user_list(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        User::factory()->create([
            'name' => '表示対象教員',
            'role' => UserRole::Teacher,
        ]);

        User::factory()->create([
            'name' => '非表示対象生徒',
            'role' => UserRole::Student,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'role' => UserRole::Teacher->value,
            ]));

        $response
            ->assertOk()
            ->assertSee('表示対象教員')
            ->assertDontSee('非表示対象生徒');
    }

    public function test_admin_can_search_users_by_keyword(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        User::factory()->create([
            'name' => '検索対象ユーザー',
        ]);

        User::factory()->create([
            'name' => '別ユーザー',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'keyword' => '検索対象',
            ]));

        $response
            ->assertOk()
            ->assertSee('検索対象ユーザー')
            ->assertDontSee('別ユーザー');
    }
}
