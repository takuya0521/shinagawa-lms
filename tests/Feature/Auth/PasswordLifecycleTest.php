<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PasswordLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_reset_and_personal_change_produce_usable_login_passwords(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]);
        $data = ['name' => 'Password Test', 'email' => 'policy@example.com', 'role' => 'admin', 'status' => 'active'];
        $password = 'Strong Pass123!';
        $this->actingAs($admin)->post(route('admin.users.store'), $data + ['password' => $password, 'password_confirmation' => $password])->assertSessionHasNoErrors();
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $user->email, 'password' => $password])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'));

        $reset = 'Reset Pass123!';
        $this->actingAs($admin)->put(route('admin.users.update', $user), $data + ['password' => $reset, 'password_confirmation' => $reset])->assertSessionHasNoErrors();
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $user->email, 'password' => $reset])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);

        $new = 'ChangedPass123';
        $this->put(route('account.password.update'), ['current_password' => $reset, 'password' => $new, 'password_confirmation' => $new])->assertSessionHasNoErrors();
        $before = $user->fresh()->password;
        $bad = 'ChangedPass123日本';
        $this->put(route('account.password.update'), ['current_password' => $new, 'password' => $bad, 'password_confirmation' => $bad])->assertSessionHasErrors('password');
        $this->assertSame($before, $user->fresh()->password);
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $user->email, 'password' => $new])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_cannot_register_or_reset_to_non_ascii_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]);
        $data = ['name' => 'Password Test', 'email' => 'policy@example.com', 'role' => 'admin', 'status' => 'active'];
        $bad = 'StrongPass123!日本';
        $this->actingAs($admin)->post(route('admin.users.store'), $data + ['password' => $bad, 'password_confirmation' => $bad])->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        $user = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]);
        $before = $user->password;
        $this->put(route('admin.users.update', $user), array_merge($data, ['email' => $user->email, 'password' => $bad, 'password_confirmation' => $bad]))->assertSessionHasErrors('password');
        $this->assertSame($before, $user->fresh()->password);
    }
}
