<?php

namespace App\Enums;

enum DayOfWeek: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    /**
     * 曜日の日本語名称を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Monday => '月曜日',
            self::Tuesday => '火曜日',
            self::Wednesday => '水曜日',
            self::Thursday => '木曜日',
            self::Friday => '金曜日',
            self::Saturday => '土曜日',
            self::Sunday => '日曜日',
        };
    }

    /**
     * 週間時間割で使用する短縮名称を返す。
     *
     * @return string 取得した文字列
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Monday => '月',
            self::Tuesday => '火',
            self::Wednesday => '水',
            self::Thursday => '木',
            self::Friday => '金',
            self::Saturday => '土',
            self::Sunday => '日',
        };
    }
}
