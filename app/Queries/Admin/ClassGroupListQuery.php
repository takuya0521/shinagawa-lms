<?php

namespace App\Queries\Admin;

use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ClassGroupListQuery
{
    /**
     * 管理者向けクラス一覧を取得する。
     *
     * @param ?string $keyword 検索キーワード
     * @param ?MasterStatus $status 設定する状態
     * @return LengthAwarePaginator<int, ClassGroup>
     */
    public function execute(
        ?string $keyword,
        ?MasterStatus $status,
    ): LengthAwarePaginator {
        return ClassGroup::query()
            ->withCount('students')
            ->when(
                $keyword !== null,
                function (Builder $query) use ($keyword): void {
                    $query->where(
                        function (Builder $searchQuery) use (
                            $keyword,
                        ): void {
                            $searchQuery
                                ->where(
                                    'class_code',
                                    'like',
                                    '%'.$keyword.'%',
                                )
                                ->orWhere(
                                    'class_name',
                                    'like',
                                    '%'.$keyword.'%',
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    '%'.$keyword.'%',
                                );
                        },
                    );
                },
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where(
                    'status',
                    $status->value,
                ),
            )
            ->orderBy('class_code')
            ->paginate(20)
            ->withQueryString();
    }
}
