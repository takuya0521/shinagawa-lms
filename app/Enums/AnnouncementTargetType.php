<?php

namespace App\Enums;

enum AnnouncementTargetType: string
{
    case All = 'all';
    case Role = 'role';
    case Grade = 'grade';
    case ClassGroup = 'class_group';

    /**
     * 画面表示用の対象種別名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::All => '全員',
            self::Role => 'ロール',
            self::Grade => '学年',
            self::ClassGroup => 'クラス',
        };
    }
}
