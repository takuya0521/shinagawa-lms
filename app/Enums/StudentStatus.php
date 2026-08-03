<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Graduated = 'graduated';
    case Withdrawn = 'withdrawn';

    /**
     * 画面表示用の名称を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => '在籍',
            self::Suspended => '停止',
            self::Graduated => '卒業',
            self::Withdrawn => '退学',
        };
    }

    /**
     * 通常の在籍生徒として扱う状態か判定する。
     *
     * @return bool 判定結果
     */
    public function isEnrolled(): bool
    {
        return $this === self::Active;
    }
}
