<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Google Classroomの課題情報を画面表示用の不変データへ変換する。
 */
final readonly class GoogleClassroomCourseWork
{
    /**
     * @param  string  $id  課題ID
     * @param  string  $title  課題名
     * @param  ?string  $description  課題説明
     * @param  string  $state  公開状態
     * @param  string  $workType  課題種別
     * @param  ?string  $alternateLink  Classroom Web画面URL
     * @param  ?CarbonImmutable  $dueAt  提出期限
     * @param  ?float  $maxPoints  満点
     * @param  ?CarbonImmutable  $updatedAt  最終更新日時
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $state,
        public string $workType,
        public ?string $alternateLink,
        public ?CarbonImmutable $dueAt,
        public ?float $maxPoints,
        public ?CarbonImmutable $updatedAt,
    ) {}

    /**
     * Classroomの課題種別を日本語表示へ変換する。
     *
     * @return string 画面表示用種別
     */
    public function workTypeLabel(): string
    {
        return match ($this->workType) {
            'ASSIGNMENT' => '課題',
            'SHORT_ANSWER_QUESTION' => '記述式質問',
            'MULTIPLE_CHOICE_QUESTION' => '選択式質問',
            default => 'クラスワーク',
        };
    }

    /**
     * 公開状態を日本語表示へ変換する。
     *
     * @return string 画面表示用状態
     */
    public function stateLabel(): string
    {
        return match ($this->state) {
            'PUBLISHED' => '公開中',
            'DRAFT' => '下書き',
            'DELETED' => '削除済み',
            default => '状態不明',
        };
    }
}
