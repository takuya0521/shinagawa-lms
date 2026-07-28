<?php

namespace Tests\Unit\Enums;

use App\Enums\UserStatus;
use PHPUnit\Framework\TestCase;

final class UserStatusTest extends TestCase
{
    public function test_active_status_can_login(): void
    {
        $this->assertTrue(UserStatus::Active->canLogin());
    }

    public function test_suspended_status_cannot_login(): void
    {
        $this->assertFalse(UserStatus::Suspended->canLogin());
    }

    public function test_status_has_japanese_label(): void
    {
        $this->assertSame('利用中', UserStatus::Active->label());
        $this->assertSame('利用停止', UserStatus::Suspended->label());
    }
}
