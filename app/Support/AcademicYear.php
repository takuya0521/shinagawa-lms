<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class AcademicYear
{
    private const START_MONTH = 4;

    /**
     * 指定日時が属する年度を返す。
     *
     * 4月から12月は暦年、1月から3月は前年を年度とする。
     *
     * @param ?CarbonInterface $date 基準日
     * @return int 取得した整数
     */
    public static function forDate(?CarbonInterface $date = null): int
    {
        $targetDate = $date ?? now();

        return $targetDate->month >= self::START_MONTH
            ? $targetDate->year
            : $targetDate->year - 1;
    }
}
