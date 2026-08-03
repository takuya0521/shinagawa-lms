<?php

namespace App\Services;

final class AnnouncementSanitizer
{
    /**
     * お知らせ本文をプレーンテキストとして保存できる形へ整える。
     *
     * @param string $value 処理対象値
     * @return string 取得した文字列
     */
    public function sanitizePlainText(string $value): string
    {
        return trim(strip_tags($value));
    }
}
