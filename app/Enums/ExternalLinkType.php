<?php

namespace App\Enums;

enum ExternalLinkType: string
{
    case Classroom = 'classroom';
    case Calendar = 'calendar';
    case Forms = 'forms';
    case Chat = 'chat';
    case Drive = 'drive';
    case Meet = 'meet';

    /**
     * 画面表示用のリンク種別名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Classroom => 'Google Classroom',
            self::Calendar => 'Google Calendar',
            self::Forms => 'Google Forms',
            self::Chat => 'Google Chat',
            self::Drive => 'Google Drive',
            self::Meet => 'Google Meet',
        };
    }
}
