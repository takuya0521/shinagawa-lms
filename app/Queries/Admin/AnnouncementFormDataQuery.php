<?php

namespace App\Queries\Admin;

use App\Data\Admin\AnnouncementEditorData;
use App\Data\Admin\AnnouncementFormData;
use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\ClassGroup;

/**
 * お知らせ登録・編集画面の表示データを取得する。
 */
final class AnnouncementFormDataQuery
{
    /**
     * お知らせ登録画面の表示データを取得する。
     *
     * @return AnnouncementEditorData お知らせ登録画面の表示データ
     */
    public function forCreate(): AnnouncementEditorData
    {
        return new AnnouncementEditorData(
            formData: $this->formData(),
            announcement: new Announcement,
            selectedTargets: [AnnouncementTargetType::All->value],
        );
    }

    /**
     * お知らせ編集画面の表示データを取得する。
     *
     * @param Announcement $announcement 編集対象のお知らせ
     * @return AnnouncementEditorData お知らせ編集画面の表示データ
     */
    public function forEdit(
        Announcement $announcement,
    ): AnnouncementEditorData {
        $announcement->load(['targets', 'creator', 'updater']);

        /** @var list<string> $selectedTargets */
        $selectedTargets = array_values(
            $announcement->targets
                ->map(
                    static function (AnnouncementTarget $target): string {
                        if ($target->target_type === AnnouncementTargetType::All) {
                            return AnnouncementTargetType::All->value;
                        }

                        return $target->target_type->value
                            .':'
                            .$target->target_value;
                    },
                )
                ->all(),
        );

        return new AnnouncementEditorData(
            formData: $this->formData(),
            announcement: $announcement,
            selectedTargets: $selectedTargets,
        );
    }

    /**
     * お知らせフォームの共通選択肢を取得する。
     *
     * @return AnnouncementFormData お知らせフォームの共通選択肢
     */
    private function formData(): AnnouncementFormData
    {
        return new AnnouncementFormData(
            noticeTypes: AnnouncementNoticeType::cases(),
            statuses: AnnouncementStatus::cases(),
            grades: Grade::cases(),
            roles: [UserRole::Teacher, UserRole::Student],
            classGroups: ClassGroup::query()
                ->orderBy('class_code')
                ->get(),
        );
    }
}
