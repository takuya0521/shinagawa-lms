<?php

namespace App\Enums;

enum LessonStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * 画面表示用の授業実施状態名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => '予定',
            self::Completed => '実施済',
            self::Cancelled => '休講',
        };
    }
}
