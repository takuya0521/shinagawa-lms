<?php

namespace App\Data\Admin;

use Carbon\CarbonImmutable;

/**
 * 操作ログ一覧とCSV出力で共通利用する検索条件を保持する。
 */
final readonly class OperationLogFilters
{
    /**
     * 操作ログの検索条件を生成する。
     *
     * @param CarbonImmutable $dateFrom 検索開始日時
     * @param CarbonImmutable $dateTo 検索終了日時
     * @param int|null $userId 操作者ユーザーID
     * @param string|null $action 操作コード
     * @param string|null $targetTable 対象テーブル名
     * @param int|null $targetId 対象データID
     * @param string|null $keyword 操作者・操作・詳細を対象とする検索語
     */
    public function __construct(
        public CarbonImmutable $dateFrom,
        public CarbonImmutable $dateTo,
        public ?int $userId,
        public ?string $action,
        public ?string $targetTable,
        public ?int $targetId,
        public ?string $keyword,
    ) {}

    /**
     * Bladeへ表示する検索条件へ変換する。
     *
     * @return array<string, mixed> 操作ログ一覧画面へ渡す検索条件
     */
    public function toViewData(): array
    {
        return [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'selectedUserId' => $this->userId,
            'selectedAction' => $this->action,
            'selectedTargetTable' => $this->targetTable,
            'selectedTargetId' => $this->targetId,
            'keyword' => $this->keyword,
        ];
    }

    /**
     * 操作ログCSV出力の監査記録へ保存する形式へ変換する。
     *
     * @return array<string, int|string|null> 監査記録へ保存する検索条件
     */
    public function toAuditData(): array
    {
        return [
            'date_from' => $this->dateFrom->format('Y-m-d'),
            'date_to' => $this->dateTo->format('Y-m-d'),
            'user_id' => $this->userId,
            'action' => $this->action,
            'target_table' => $this->targetTable,
            'target_id' => $this->targetId,
            'keyword' => $this->keyword,
        ];
    }
}
