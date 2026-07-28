<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('admin.dashboard');
    }

    public function test_teacher_is_redirected_to_teacher_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('teacher.dashboard');
    }

    public function test_student_is_redirected_to_student_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('student.dashboard');
    }

    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_suspended_user_is_logged_out(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Suspended,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }
}
