<?php

namespace App\Queries\Admin;

use App\Enums\MasterStatus;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SubjectListQuery
{
    /**
     * 管理画面へ表示する科目一覧を取得する。
     *
     * @param string $keyword 検索キーワード
     * @param ?MasterStatus $status 設定する状態
     * @return LengthAwarePaginator<int, Subject>
     */
    public function execute(
        string $keyword,
        ?MasterStatus $status,
    ): LengthAwarePaginator {
        return Subject::query()
            ->when(
                $keyword !== '',
                function (
                    Builder $query,
                ) use ($keyword): void {
                    $query->where(
                        function (
                            Builder $keywordQuery,
                        ) use ($keyword): void {
                            $keywordQuery
                                ->where(
                                    'subject_code',
                                    'like',
                                    "%{$keyword}%",
                                )
                                ->orWhere(
                                    'subject_name',
                                    'like',
                                    "%{$keyword}%",
                                );
                        },
                    );
                },
            )
            ->when(
                $status !== null,
                function (
                    Builder $query,
                ) use ($status): void {
                    $query->where(
                        'status',
                        $status->value,
                    );
                },
            )
            ->orderBy('subject_code')
            ->paginate(20)
            ->withQueryString();
    }
}
