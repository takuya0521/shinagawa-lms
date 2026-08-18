<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Google Classroomのクラス情報を画面表示用の不変データへ変換する。
 */
final readonly class GoogleClassroomCourse
{
    /**
     * @param  string  $id  ClassroomクラスID
     * @param  string  $name  クラス名
     * @param  ?string  $section  セクション名
     * @param  ?string  $description  クラス説明
     * @param  ?string  $room  教室名
     * @param  string  $state  Classroom上のクラス状態
     * @param  ?string  $alternateLink  Classroom Web画面URL
     * @param  ?string  $enrollmentCode  参加コード
     * @param  ?CarbonImmutable  $updatedAt  最終更新日時
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $section,
        public ?string $description,
        public ?string $room,
        public string $state,
        public ?string $alternateLink,
        public ?string $enrollmentCode,
        public ?CarbonImmutable $updatedAt,
    ) {}

    /**
     * Classroomの状態を日本語表示へ変換する。
     *
     * @return string 画面表示用状態
     */
    public function stateLabel(): string
    {
        return match ($this->state) {
            'ACTIVE' => '利用中',
            'ARCHIVED' => 'アーカイブ',
            'PROVISIONED' => '準備中',
            'DECLINED' => '辞退',
            'SUSPENDED' => '停止中',
            default => '状態不明',
        };
    }
}
