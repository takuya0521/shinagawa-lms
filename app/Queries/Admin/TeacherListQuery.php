<?php

namespace App\Queries\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserStatus;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class TeacherListQuery
{
    /**
     * 管理者向け教員一覧を取得する。
     *
     * @param ?string $keyword 検索キーワード
     * @param ?MasterStatus $teacherStatus 教員状態
     * @param ?UserStatus $accountStatus アカウント状態
     * @return LengthAwarePaginator<int, Teacher>
     */
    public function execute(
        ?string $keyword,
        ?MasterStatus $teacherStatus,
        ?UserStatus $accountStatus,
    ): LengthAwarePaginator {
        return Teacher::query()
            ->with('user')
            ->when(
                $keyword !== null,
                function (Builder $query) use ($keyword): void {
                    $query->where(
                        function (Builder $searchQuery) use ($keyword): void {
                            $searchQuery
                                ->where(
                                    'subject_notes',
                                    'like',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereHas(
                                    'user',
                                    function (Builder $userQuery) use ($keyword): void {
                                        $userQuery
                                            ->where(
                                                'name',
                                                'like',
                                                '%'.$keyword.'%',
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
                                                '%'.$keyword.'%',
                                            );
                                    },
                                );
                        },
                    );
                },
            )
            ->when(
                $teacherStatus !== null,
                fn (Builder $query): Builder => $query->where(
                    'status',
                    $teacherStatus->value,
                ),
            )
            ->when(
                $accountStatus !== null,
                fn (Builder $query): Builder => $query->whereHas(
                    'user',
                    fn (Builder $userQuery): Builder => $userQuery->where(
                        'status',
                        $accountStatus->value,
                    ),
                ),
            )
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
    }
}
