<?php

namespace App\Queries\Admin;

use App\Data\Admin\OperationLogFilters;
use App\Models\OperationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * 管理者向け操作ログを検索する。
 */
final class OperationLogListQuery
{
    /**
     * 管理者向け操作ログ一覧を取得する。
     *
     * @param  OperationLogFilters  $filters  検索条件
     * @return LengthAwarePaginator<int, OperationLog> 1ページ50件の操作ログ一覧
     */
    public function paginate(
        OperationLogFilters $filters,
    ): LengthAwarePaginator {
        return $this
            ->build($filters)
            ->orderByDesc('operation_logs.created_at')
            ->orderByDesc('operation_logs.id')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * 操作ログの共通検索クエリを生成する。
     *
     * @param  OperationLogFilters  $filters  検索条件
     * @return Builder<OperationLog> 検索条件を適用したクエリ
     */
    public function build(
        OperationLogFilters $filters,
    ): Builder {
        $query = OperationLog::query()
            ->with('user')
            ->where('operation_logs.created_at', '>=', $filters->dateFrom)
            ->where('operation_logs.created_at', '<=', $filters->dateTo)
            ->when(
                $filters->userId !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    'operation_logs.user_id',
                    $filters->userId,
                ),
            )
            ->when(
                $filters->action !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    'operation_logs.action',
                    $filters->action,
                ),
            )
            ->when(
                $filters->targetTable !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    'operation_logs.target_table',
                    $filters->targetTable,
                ),
            )
            ->when(
                $filters->targetId !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    'operation_logs.target_id',
                    $filters->targetId,
                ),
            );

        if ($filters->keyword !== null) {
            $this->applyKeywordFilter($query, $filters->keyword);
        }

        return $query;
    }

    /**
     * 操作コード・対象テーブル・詳細JSON・操作者へキーワード条件を適用する。
     *
     * @param  Builder<OperationLog>  $query  操作ログ検索クエリ
     * @param  string  $keyword  検索語
     * @return void 戻り値なし
     */
    private function applyKeywordFilter(
        Builder $query,
        string $keyword,
    ): void {
        $like = '%'.$keyword.'%';

        $query->where(
            static function (Builder $keywordQuery) use ($like): void {
                $keywordQuery
                    ->whereLike('operation_logs.action', $like)
                    ->orWhereLike('operation_logs.target_table', $like)
                    // JSONBは文字列LIKEを直接適用できないため、テキストへ変換して検索する。
                    ->orWhereRaw(
                        'CAST(operation_logs.detail AS TEXT) ILIKE ?',
                        [$like],
                    )
                    ->orWhereHas(
                        'user',
                        static function (Builder $userQuery) use ($like): void {
                            $userQuery
                                ->whereLike('name', $like)
                                ->orWhereLike('email', $like);
                        },
                    );
            },
        );
    }
}
