<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';

    /**
     * 画面表示用の評価状態名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => '下書き',
            self::Confirmed => '確定',
        };
    }

    /**
     * 生徒へ公開できる状態か判定する。
     *
     * @return bool 判定結果
     */
    public function isPublished(): bool
    {
        return $this === self::Confirmed;
    }
}
