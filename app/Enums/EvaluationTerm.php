<?php

namespace App\Enums;

enum EvaluationTerm: string
{
    case Annual = 'annual';

    /**
     * 画面表示用の評価期間名を返す。
     *
     * @return string 取得した文字列
     */
    public function label(): string
    {
        return match ($this) {
            self::Annual => '通年',
        };
    }
}
