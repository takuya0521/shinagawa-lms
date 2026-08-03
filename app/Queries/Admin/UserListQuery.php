<?php

namespace App\Queries\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class UserListQuery
{
    /**
     * 管理者向けユーザー一覧を取得する。
     *
     * @param ?string $keyword 検索キーワード
     * @param ?UserRole $role ロール
     * @param ?UserStatus $status 設定する状態
     * @return LengthAwarePaginator<int, User>
     */
    public function execute(
        ?string $keyword,
        ?UserRole $role,
        ?UserStatus $status,
    ): LengthAwarePaginator {
        return User::query()
            ->when(
                $keyword !== null,
                function (Builder $query) use ($keyword): void {
                    $query->where(
                        function (Builder $keywordQuery) use ($keyword): void {
                            $keywordQuery
                                ->where('name', 'like', '%'.$keyword.'%')
                                ->orWhere('email', 'like', '%'.$keyword.'%');
                        },
                    );
                },
            )
            ->when(
                $role !== null,
                fn (Builder $query): Builder => $query->where(
                    'role',
                    $role->value,
                ),
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where(
                    'status',
                    $status->value,
                ),
            )
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
    }
}
