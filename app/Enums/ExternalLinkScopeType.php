<?php

namespace App\Enums;

enum ExternalLinkScopeType: string
{
    case Global = 'global';
    case Role = 'role';
    case ClassGroup = 'class_group';
    case Course = 'course';
    case Student = 'student';

    /**
     * 画面表示用のスコープ種別名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Global => '全体',
            self::Role => 'ロール',
            self::ClassGroup => 'クラス',
            self::Course => '授業',
            self::Student => '生徒',
        };
    }
}
