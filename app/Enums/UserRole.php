<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * 画面表示用のロール名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => '管理者',
            self::Teacher => '教員',
            self::Student => '生徒',
        };
    }

    /**
     * 外部リンクのロールスコープで使用する内部IDを返す。
     *
     * @return int 取得した整数
     */
    public function scopeId(): int
    {
        return match ($this) {
            self::Admin => 1,
            self::Teacher => 2,
            self::Student => 3,
        };
    }

    /**
     * 外部リンクの内部IDからロールを返す。
     *
     * @param int $scopeId 対象データの識別子
     * @return ?self 処理結果。取得できない場合はnull
     */
    public static function fromScopeId(int $scopeId): ?self
    {
        return match ($scopeId) {
            1 => self::Admin,
            2 => self::Teacher,
            3 => self::Student,
            default => null,
        };
    }
}
