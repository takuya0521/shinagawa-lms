<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case EarlyLeave = 'early_leave';

    /**
     * 画面表示用の出欠区分名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Present => '出席',
            self::Absent => '欠席',
            self::Late => '遅刻',
            self::EarlyLeave => '早退',
        };
    }
}
