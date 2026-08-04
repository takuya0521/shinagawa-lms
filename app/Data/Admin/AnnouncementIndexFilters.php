<?php

namespace App\Data\Admin;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;

/**
 * 管理者向けお知らせ一覧の検索条件を保持する。
 */
final readonly class AnnouncementIndexFilters
{
    /**
     * お知らせ一覧の検索条件を生成する。
     *
     * @param  string  $keyword  タイトル・本文を対象とする検索語
     * @param  AnnouncementNoticeType|null  $noticeType  お知らせ種別
     * @param  string|null  $target  公開対象
     * @param  AnnouncementStatus|null  $status  公開状態
     * @param  bool|null  $importantOnly  重要なお知らせだけを表示するか
     * @param  string|null  $publishFrom  掲載期間の検索開始日
     * @param  string|null  $publishTo  掲載期間の検索終了日
     */
    public function __construct(
        public string $keyword,
        public ?AnnouncementNoticeType $noticeType,
        public ?string $target,
        public ?AnnouncementStatus $status,
        public ?bool $importantOnly,
        public ?string $publishFrom,
        public ?string $publishTo,
    ) {}

    /**
     * Bladeへ表示する検索条件へ変換する。
     *
     * @return array<string, mixed> お知らせ一覧画面へ渡す検索条件
     */
    public function toViewData(): array
    {
        return [
            'keyword' => $this->keyword,
            'selectedNoticeType' => $this->noticeType?->value,
            'selectedTarget' => $this->target,
            'selectedStatus' => $this->status?->value,
            'importantOnly' => $this->importantOnly === true,
            'publishFrom' => $this->publishFrom,
            'publishTo' => $this->publishTo,
        ];
    }
}
