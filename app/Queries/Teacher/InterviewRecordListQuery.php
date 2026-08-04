<?php

namespace App\Queries\Teacher;

use App\Models\InterviewRecord;
use App\Models\Teacher;
use App\Queries\Interview\AssignedStudentQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class InterviewRecordListQuery
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param  AssignedStudentQuery  $assignedStudentQuery  データ取得処理
     */
    public function __construct(
        private readonly AssignedStudentQuery $assignedStudentQuery,
    ) {}

    /**
     * ログイン教員の担当生徒に関する面談履歴を取得する。
     *
     * @param  Teacher  $teacher  対象教員
     * @param  CarbonImmutable  $dateFrom  検索開始日
     * @param  CarbonImmutable  $dateTo  検索終了日
     * @param  ?string  $keyword  検索キーワード
     * @param  ?int  $studentId  対象生徒ID
     * @param  ?string  $interviewType  面談種別
     * @return LengthAwarePaginator<int, InterviewRecord>
     */
    public function execute(
        Teacher $teacher,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        ?string $keyword,
        ?int $studentId,
        ?string $interviewType,
    ): LengthAwarePaginator {
        $assignedStudents = $this->assignedStudentQuery
            ->builder($teacher)
            ->select('students.id');

        return InterviewRecord::query()
            ->with([
                'student.classGroup',
                'teacher.user',
                'creator',
                'updater',
            ])
            ->where('teacher_id', $teacher->id)
            ->whereIn('student_id', $assignedStudents)
            ->whereDate(
                'interview_date',
                '>=',
                $dateFrom->format('Y-m-d'),
            )
            ->whereDate(
                'interview_date',
                '<=',
                $dateTo->format('Y-m-d'),
            )
            ->when(
                $keyword !== null,
                function (Builder $query) use ($keyword): void {
                    $query->where(
                        function (Builder $keywordQuery) use ($keyword): void {
                            $keywordQuery
                                ->whereLike(
                                    'interview_type',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereLike(
                                    'memo',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereLike(
                                    'next_action',
                                    '%'.$keyword.'%',
                                )
                                ->orWhereHas(
                                    'student',
                                    function (Builder $studentQuery) use ($keyword): void {
                                        $studentQuery
                                            ->whereLike(
                                                'student_no',
                                                '%'.$keyword.'%',
                                            )
                                            ->orWhereLike(
                                                'student_name',
                                                '%'.$keyword.'%',
                                            );
                                    },
                                );
                        },
                    );
                },
            )
            ->when(
                $studentId !== null,
                fn (Builder $query): Builder => $query->where(
                    'student_id',
                    $studentId,
                ),
            )
            ->when(
                $interviewType !== null,
                fn (Builder $query): Builder => $query->whereLike(
                    'interview_type',
                    '%'.$interviewType.'%',
                ),
            )
            ->orderByDesc('interview_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();
    }
}
