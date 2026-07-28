<?php

namespace App\Enums;

enum MasterStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * 画面表示用の名称を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => '有効',
            self::Inactive => '無効',
        };
    }

    /**
     * 選択肢として利用可能な状態か判定する。
     */
    public function isAvailable(): bool
    {
        return $this === self::Active;
    }
}
