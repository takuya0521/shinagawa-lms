<?php

namespace App\Enums;

enum AnnouncementNoticeType: string
{
    case School = 'school';
    case Grade = 'grade';
    case Office = 'office';

    /**
     * 画面表示用のお知らせ種別名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::School => '学校共通',
            self::Grade => '学年連絡',
            self::Office => '事務連絡',
        };
    }
}
