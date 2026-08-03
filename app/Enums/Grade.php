<?php

namespace App\Enums;

enum Grade: string
{
    case First = '1';
    case Second = '2';
    case Third = '3';

    /**
     * 画面表示用の学年名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::First => '1年',
            self::Second => '2年',
            self::Third => '3年',
        };
    }
}
