<?php

namespace App\Data\Admin;

use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * 操作ログ一覧画面へ渡す表示データを保持する。
 */
final readonly class OperationLogIndexData
{
    /**
     * 操作ログ一覧画面の表示データを生成する。
     *
     * @param  LengthAwarePaginator<int, OperationLog>  $operationLogs  操作ログ一覧
     * @param  EloquentCollection<int, User>  $users  操作者選択肢
     * @param  Collection<int, array{value: string, label: string}>  $actions  操作コード選択肢
     * @param  Collection<int, array{value: string, label: string}>  $targetTables  対象テーブル選択肢
     * @param  OperationLogFilters  $filters  選択中の検索条件
     */
    public function __construct(
        public LengthAwarePaginator $operationLogs,
        public EloquentCollection $users,
        public Collection $actions,
        public Collection $targetTables,
        public OperationLogFilters $filters,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 操作ログ一覧画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            'operationLogs' => $this->operationLogs,
            'users' => $this->users,
            'actions' => $this->actions,
            'targetTables' => $this->targetTables,
            ...$this->filters->toViewData(),
        ];
    }
}
