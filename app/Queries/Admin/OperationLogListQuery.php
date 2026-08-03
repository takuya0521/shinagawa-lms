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
     * @param OperationLogFilters $filters 検索条件
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
     * @param OperationLogFilters $filters 検索条件
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
     * @param Builder<OperationLog> $query 操作ログ検索クエリ
     * @param string $keyword 検索語
     *
     * @return void 戻り値なし
     */
    private function applyKeywordFilter(
        Builder $query,
        string $keyword,
    ): void {
        $like = '%'.$keyword.'%';
        $detailPatterns = $this->detailSearchPatterns($keyword);

        $query->where(
            static function (Builder $keywordQuery) use (
                $like,
                $detailPatterns,
            ): void {
                $keywordQuery
                    ->where('operation_logs.action', 'like', $like)
                    ->orWhere('operation_logs.target_table', 'like', $like);

                foreach ($detailPatterns as $detailPattern) {
                    $keywordQuery->orWhere(
                        'operation_logs.detail',
                        'like',
                        $detailPattern,
                    );
                }

                $keywordQuery->orWhereHas(
                    'user',
                    static function (Builder $userQuery) use ($like): void {
                        $userQuery
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    },
                );
            },
        );
    }

    /**
     * DBごとに異なるJSON保存形式を考慮した詳細検索パターンを返す。
     *
     * @param string $keyword 検索語
     * @return list<string> LIKE検索へ使用するパターン一覧
     */
    private function detailSearchPatterns(
        string $keyword,
    ): array {
        $patterns = ['%'.$keyword.'%'];

        // SQLiteでは日本語がUnicodeエスケープされたJSON文字列で保存される場合がある。
        $jsonEncodedKeyword = json_encode($keyword, JSON_THROW_ON_ERROR);
        $jsonEscapedKeyword = substr($jsonEncodedKeyword, 1, -1);

        if ($jsonEscapedKeyword !== $keyword) {
            $patterns[] = '%'.$jsonEscapedKeyword.'%';
        }

        return array_values(array_unique($patterns));
    }
}
