<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * 画面表示用のロール名を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => '管理者',
            self::Teacher => '教員',
            self::Student => '生徒',
        };
    }
}
