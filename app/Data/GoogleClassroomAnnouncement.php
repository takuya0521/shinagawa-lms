<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Google Classroomのお知らせ情報を画面表示用の不変データへ変換する。
 */
final readonly class GoogleClassroomAnnouncement
{
    /**
     * @param  string  $id  お知らせID
     * @param  string  $text  お知らせ本文
     * @param  string  $state  公開状態
     * @param  ?string  $alternateLink  Classroom Web画面URL
     * @param  ?CarbonImmutable  $updatedAt  最終更新日時
     */
    public function __construct(
        public string $id,
        public string $text,
        public string $state,
        public ?string $alternateLink,
        public ?CarbonImmutable $updatedAt,
    ) {}
}
