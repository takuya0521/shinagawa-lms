<?php

namespace Tests\Unit\Enums;

use App\Enums\UserStatus;
use PHPUnit\Framework\TestCase;

/**
 * ユーザー状態Enumのログイン可否と表示名を確認する単体テスト。
 *
 * 有効・停止状態のログイン可否、および日本語ラベルを検証する。
 */
final class UserStatusTest extends TestCase
{
    /**
     * 有効状態のユーザーがログイン可能と判定されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象Enumの表示名またはログイン可否判定を呼び出す。
     * 期待結果: 対象条件が真になることを確認する。
     */
    public function test_active_status_can_login(): void
    {
        $this->assertTrue(UserStatus::Active->canLogin());
    }

    /**
     * 利用停止状態のユーザーがログイン不可と判定されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象Enumの表示名またはログイン可否判定を呼び出す。
     * 期待結果: 対象条件が偽になることを確認する。
     */
    public function test_suspended_status_cannot_login(): void
    {
        $this->assertFalse(UserStatus::Suspended->canLogin());
    }

    /**
     * ユーザー状態Enumが設計どおりの日本語ラベルを返すことを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: 対象Enumの表示名またはログイン可否判定を呼び出す。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    public function test_status_has_japanese_label(): void
    {
        $this->assertSame('利用中', UserStatus::Active->label());
        $this->assertSame('利用停止', UserStatus::Suspended->label());
    }
}
