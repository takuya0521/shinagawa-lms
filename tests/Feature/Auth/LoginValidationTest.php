<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * ログイン画面の入力検証を確認するフィーチャーテスト。
 */
final class LoginValidationTest extends TestCase
{
    /**
     * メールアドレスが空欄の場合は入力エラーになることを確認する。
     */
    public function test_login_email_is_required(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => '',
                'password' => 'Test1234!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email');
    }

    /**
     * メールアドレス形式ではない値を拒否することを確認する。
     */
    public function test_login_email_must_be_valid_email_address(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'invalid-email',
                'password' => 'Test1234!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email');
    }

    /**
     * メールアドレスは255文字以内でなければならないことを確認する。
     */
    public function test_login_email_cannot_exceed_255_characters(): void
    {
        $email = str_repeat('a', 244).'@example.com';
        self::assertSame(256, strlen($email));

        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => $email,
                'password' => 'Test1234!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email');
    }

    /**
     * パスワードが空欄の場合は入力エラーになることを確認する。
     */
    public function test_login_password_is_required(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'test@example.com',
                'password' => '',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('password');
    }
}
