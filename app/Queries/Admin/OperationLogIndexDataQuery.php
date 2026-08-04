<?php

namespace App\Queries\Admin;

use App\Data\Admin\OperationLogFilters;
use App\Data\Admin\OperationLogIndexData;
use App\Models\OperationLog;
use App\Models\User;
use App\Support\OperationLogPresenter;
use Illuminate\Support\Collection;

/**
 * 操作ログ一覧画面の表示データを取得する。
 */
final class OperationLogIndexDataQuery
{
    /**
     * 表示データ取得処理を生成する。
     *
     * @param  OperationLogListQuery  $listQuery  操作ログ一覧の検索処理
     */
    public function __construct(
        private readonly OperationLogListQuery $listQuery,
    ) {}

    /**
     * 操作ログ一覧画面の表示データを取得する。
     *
     * @param  OperationLogFilters  $filters  検索条件
     * @return OperationLogIndexData 操作ログ一覧画面の表示データ
     */
    public function execute(
        OperationLogFilters $filters,
    ): OperationLogIndexData {
        return new OperationLogIndexData(
            operationLogs: $this->listQuery->paginate($filters),
            users: User::query()
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name', 'email']),
            actions: $this->actions(),
            targetTables: $this->targetTables(),
            filters: $filters,
        );
    }

    /**
     * 操作コードの選択肢を取得する。
     *
     * @return Collection<int, array{value: string, label: string}> 操作コード選択肢
     */
    private function actions(): Collection
    {
        return OperationLog::query()
            ->whereNotNull('action')
            ->where('action', '<>', '')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->filter(static fn (mixed $value): bool => is_string($value))
            ->map(static fn (mixed $value): array => [
                'value' => (string) $value,
                'label' => OperationLogPresenter::actionLabel((string) $value),
            ])
            ->values();
    }

    /**
     * 対象テーブルの選択肢を取得する。
     *
     * @return Collection<int, array{value: string, label: string}> 対象テーブル選択肢
     */
    private function targetTables(): Collection
    {
        return OperationLog::query()
            ->whereNotNull('target_table')
            ->where('target_table', '<>', '')
            ->distinct()
            ->orderBy('target_table')
            ->pluck('target_table')
            ->filter(static fn (mixed $value): bool => is_string($value))
            ->map(static fn (mixed $value): array => [
                'value' => (string) $value,
                'label' => OperationLogPresenter::targetLabel((string) $value),
            ])
            ->values();
    }
}
