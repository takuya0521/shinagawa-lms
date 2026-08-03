<?php

namespace App\Data\Admin;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use Illuminate\Database\Eloquent\Collection;

/**
 * お知らせ登録・編集画面へ渡す選択肢を保持する。
 */
final readonly class AnnouncementFormData
{
    /**
     * お知らせフォームの表示データを生成する。
     *
     * @param list<AnnouncementNoticeType> $noticeTypes お知らせ種別選択肢
     * @param list<AnnouncementStatus> $statuses 公開状態選択肢
     * @param list<Grade> $grades 学年選択肢
     * @param list<UserRole> $roles 公開対象ロール選択肢
     * @param Collection<int, ClassGroup> $classGroups クラス選択肢
     */
    public function __construct(
        public array $noticeTypes,
        public array $statuses,
        public array $grades,
        public array $roles,
        public Collection $classGroups,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> お知らせフォームの表示データ
     */
    public function toViewData(): array
    {
        return [
            'noticeTypes' => $this->noticeTypes,
            'statuses' => $this->statuses,
            'grades' => $this->grades,
            'roles' => $this->roles,
            'classGroups' => $this->classGroups,
        ];
    }
}
