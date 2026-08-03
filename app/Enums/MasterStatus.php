<?php

namespace App\Enums;

enum MasterStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * 画面表示用の名称を返す。
     *
     * @return string 取得した文字列
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
     *
     * @return bool 判定結果
     */
    public function isAvailable(): bool
    {
        return $this === self::Active;
    }
}
