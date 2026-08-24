<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * ログインフォームの入力チェックを確認するフィーチャーテスト。
 *
 * 単体テスト仕様書 C-001 のメールアドレス・パスワード入力チェックを、
 * 実際の /login POST を通して確認する。
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
            ->assertSessionHasErrors([
                'email' => 'メールアドレスは必須です。',
            ]);
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
            ->assertSessionHasErrors([
                'email' => 'メールアドレスは正しいメールアドレス形式で入力してください。',
            ]);
    }

    /**
     * メールアドレス255文字は入力チェックを通過し、認証処理まで進むことを確認する。
     */
    public function test_login_email_accepts_255_characters(): void
    {
        $email =
            str_repeat('a', 64)
            .'@'
            .str_repeat('b', 46)
            .'.'
            .str_repeat('c', 46)
            .'.'
            .str_repeat('d', 46)
            .'.'
            .str_repeat('e', 49);

        self::assertSame(255, strlen($email));

        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => $email,
                'password' => 'WrongPass123!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
    }

    /**
     * メールアドレス256文字は文字数上限エラーになることを確認する。
     */
    public function test_login_email_cannot_exceed_255_characters(): void
    {
        $email =
            str_repeat('a', 64)
            .'@'
            .str_repeat('b', 46)
            .'.'
            .str_repeat('c', 46)
            .'.'
            .str_repeat('d', 46)
            .'.'
            .str_repeat('e', 50);

        self::assertSame(256, strlen($email));

        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => $email,
                'password' => 'Test1234!',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'メールアドレスは255文字以内で入力してください。',
            ]);
    }

    /**
     * パスワードが空欄の場合は入力エラーになることを確認する。
     */
    public function test_login_password_is_required(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'test.admin@shinagawahs.test',
                'password' => '',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'password' => 'パスワードは必須です。',
            ]);
    }

    /**
     * ログイン状態保持には真偽値以外を受け付けないことを確認する。
     */
    public function test_login_remember_must_be_boolean_when_present(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'test.admin@shinagawahs.test',
                'password' => 'Test1234!',
                'remember' => 'invalid',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'remember' => 'ログイン状態を保持するには正しい値を指定してください。',
            ]);
    }
}