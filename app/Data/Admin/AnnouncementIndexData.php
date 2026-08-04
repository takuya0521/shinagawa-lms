<?php

namespace App\Data\Admin;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理者向けお知らせ一覧画面へ渡す表示データを保持する。
 */
final readonly class AnnouncementIndexData
{
    /**
     * お知らせ一覧画面の表示データを生成する。
     *
     * @param  LengthAwarePaginator<int, Announcement>  $announcements  お知らせ一覧
     * @param  list<AnnouncementNoticeType>  $noticeTypes  お知らせ種別選択肢
     * @param  list<AnnouncementStatus>  $statuses  公開状態選択肢
     * @param  array<string, string>  $targetOptions  公開対象選択肢
     * @param  array<int, string>  $classGroupNames  クラスIDをキーとするクラス名一覧
     * @param  AnnouncementIndexFilters  $filters  選択中の検索条件
     */
    public function __construct(
        public LengthAwarePaginator $announcements,
        public array $noticeTypes,
        public array $statuses,
        public array $targetOptions,
        public array $classGroupNames,
        public AnnouncementIndexFilters $filters,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> お知らせ一覧画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            'announcements' => $this->announcements,
            'noticeTypes' => $this->noticeTypes,
            'statuses' => $this->statuses,
            'targetOptions' => $this->targetOptions,
            'classGroupNames' => $this->classGroupNames,
            ...$this->filters->toViewData(),
        ];
    }
}
