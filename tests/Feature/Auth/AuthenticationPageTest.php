<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

final class AuthenticationPageTest extends TestCase
{
    /**
     * 未認証ユーザーはトップ画面からログイン画面へ遷移する。
     */
    public function test_guest_is_redirected_from_home_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('login');
    }

    /**
     * 未認証ユーザーがログイン画面を表示できる。
     */
    public function test_guest_can_view_login_screen(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    /**
     * 未認証ユーザーはダッシュボードを表示できない。
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirectToRoute('login');
    }
}
