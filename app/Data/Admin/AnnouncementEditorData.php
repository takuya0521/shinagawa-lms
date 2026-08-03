<?php

namespace App\Data\Admin;

use App\Models\Announcement;

/**
 * お知らせ登録・編集画面へ渡す表示データを保持する。
 */
final readonly class AnnouncementEditorData
{
    /**
     * お知らせ登録・編集画面の表示データを生成する。
     *
     * @param AnnouncementFormData $formData フォーム選択肢
     * @param Announcement $announcement 登録初期値または編集対象のお知らせ
     * @param list<string> $selectedTargets 選択中の公開対象値
     */
    public function __construct(
        public AnnouncementFormData $formData,
        public Announcement $announcement,
        public array $selectedTargets,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> お知らせ登録・編集画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            ...$this->formData->toViewData(),
            'announcement' => $this->announcement,
            'selectedTargets' => $this->selectedTargets,
        ];
    }
}
