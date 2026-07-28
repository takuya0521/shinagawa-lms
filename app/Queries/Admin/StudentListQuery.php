<?php

namespace App\Queries\Admin;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class StudentListQuery
{
    /**
     * 管理者向け生徒一覧を取得する。
     *
     * @return LengthAwarePaginator<int, Student>
     */
    public function execute(
        ?string $keyword,
        ?string $grade,
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
                                ->where(
                                    'student_no',
                                    'like',
                                    '%'.$keyword.'%',
                                )
                                ->orWhere(
                                    'student_name',
                                    'like',
                                    '%'.$keyword.'%',
                                )
                                ->orWhere(
                                    'partner_school',
                                    'like',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereHas(
                                    'user',
                                    function (Builder $userQuery) use ($keyword): void {
                                        $userQuery->where(
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
                $grade !== null,
                fn (Builder $query): Builder => $query->where(
                    'grade',
                    $grade,
                ),
            )
            ->when(
                $affiliation !== null,
                fn (Builder $query): Builder => $query->where(
                    'affiliation',
                    'like',
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
