<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * 画面表示用の利用状態名を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => '利用中',
            self::Suspended => '利用停止',
        };
    }

    /**
     * ログイン可能な利用状態か判定する。
     */
    public function canLogin(): bool
    {
        return $this === self::Active;
    }
}
