<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_password_change_page(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSeeText('パスワード変更');
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHas('success', 'パスワードを変更しました。');

        $this->assertTrue(
            Hash::check('NewPassword123', $user->refresh()->password),
        );
        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $user->id,
            'action' => 'change_password',
            'target_table' => 'users',
            'target_id' => $user->id,
        ]);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(
            Hash::check('password', $user->refresh()->password),
        );
    }

    public function test_password_confirmation_and_strength_are_required(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'weak',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('password');
    }

    public function test_guest_is_redirected_from_password_change_page(): void
    {
        $this
            ->get(route('account.password.edit'))
            ->assertRedirectToRoute('login');
    }

    private function user(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'password' => Hash::make('password'),
        ]);
    }
}
