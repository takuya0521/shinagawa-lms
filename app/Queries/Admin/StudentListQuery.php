<?php

namespace App\Queries\Admin;

use App\Enums\Grade;
use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class StudentListQuery
{
    /**
     * 管理者向け生徒一覧を取得する。
     *
     * @param  ?string  $keyword  検索キーワード
     * @param  ?Grade  $grade  学年
     * @param  ?string  $affiliation  所属条件
     * @param  ?int  $classGroupId  対象データの識別子
     * @param  ?StudentStatus  $status  設定する状態
     * @return LengthAwarePaginator<int, Student>
     */
    public function execute(
        ?string $keyword,
        ?Grade $grade,
        ?string $affiliation,
        ?int $classGroupId,
        ?StudentStatus $status,
    ): LengthAwarePaginator {
        return Student::query()
            ->with([
                'user',
                'classGroup',
            ])
            ->when(
                $keyword !== null,
                function (Builder $query) use ($keyword): void {
                    $query->where(
                        function (Builder $keywordQuery) use ($keyword): void {
                            $keywordQuery
                                ->whereLike(
                                    'student_no',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereLike(
                                    'student_name',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereLike(
                                    'partner_school',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereHas(
                                    'user',
                                    function (Builder $userQuery) use ($keyword): void {
                                        $userQuery->whereLike(
                                            'email',
                                            '%'.$keyword.'%',
                                        );
                                    },
                                );
                        },
                    );
                },
            )
            ->when(
                $grade !== null,
                fn (Builder $query): Builder => $query->where(
                    'grade',
                    $grade->value,
                ),
            )
            ->when(
                $affiliation !== null,
                fn (Builder $query): Builder => $query->whereLike(
                    'affiliation',
                    '%'.$affiliation.'%',
                ),
            )
            ->when(
                $classGroupId !== null,
                fn (Builder $query): Builder => $query->where(
                    'class_group_id',
                    $classGroupId,
                ),
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where(
                    'status',
                    $status->value,
                ),
            )
            ->orderByRaw('student_no IS NULL')
            ->orderBy('student_no')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
    }
}
