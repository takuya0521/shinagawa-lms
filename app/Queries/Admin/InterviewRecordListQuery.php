<?php

namespace App\Queries\Admin;

use App\Models\InterviewRecord;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * 管理者向け面談記録一覧を検索する。
 */
final class InterviewRecordListQuery
{
    /**
     * 管理者向け面談記録一覧を取得する。
     *
     * @param  CarbonImmutable  $dateFrom  検索開始日
     * @param  CarbonImmutable  $dateTo  検索終了日
     * @param  string|null  $keyword  キーワード
     * @param  int|null  $studentId  生徒ID
     * @param  int|null  $teacherId  教員ID
     * @param  string|null  $interviewType  面談種別
     * @return LengthAwarePaginator<int, InterviewRecord> 面談記録一覧
     */
    public function execute(
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        ?string $keyword,
        ?int $studentId,
        ?int $teacherId,
        ?string $interviewType,
    ): LengthAwarePaginator {
        $query = $this->baseQuery($dateFrom, $dateTo);
        $this->applyKeyword($query, $keyword);
        $this->applyExactFilters(
            $query,
            $studentId,
            $teacherId,
            $interviewType,
        );

        return $query
            ->orderByDesc('interview_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * 期間と関連情報読込を設定した基礎クエリを返す。
     *
     * @param  CarbonImmutable  $dateFrom  検索開始日
     * @param  CarbonImmutable  $dateTo  検索終了日
     * @return Builder<InterviewRecord> 面談記録の基礎クエリ
     */
    private function baseQuery(
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
    ): Builder {
        return InterviewRecord::query()
            ->with([
                'student.classGroup',
                'teacher.user',
                'creator',
                'updater',
            ])
            ->whereDate(
                'interview_date',
                '>=',
                $dateFrom->format('Y-m-d'),
            )
            ->whereDate(
                'interview_date',
                '<=',
                $dateTo->format('Y-m-d'),
            );
    }

    /**
     * 面談内容、生徒、教員に対するキーワード検索を適用する。
     *
     * @param  Builder<InterviewRecord>  $query  面談記録クエリ
     * @param  string|null  $keyword  キーワード
     * @return void 戻り値なし
     */
    private function applyKeyword(
        Builder $query,
        ?string $keyword,
    ): void {
        if ($keyword === null) {
            return;
        }

        $query->where(
            function (Builder $keywordQuery) use ($keyword): void {
                $keywordQuery
                    ->whereLike('interview_type', '%'.$keyword.'%')
                    ->orWhereLike('memo', '%'.$keyword.'%')
                    ->orWhereLike('next_action', '%'.$keyword.'%')
                    ->orWhereHas(
                        'student',
                        function (Builder $studentQuery) use ($keyword): void {
                            $studentQuery
                                ->whereLike('student_no', '%'.$keyword.'%')
                                ->orWhereLike(
                                    'student_name',
                                    '%'.$keyword.'%',
                                );
                        },
                    )
                    ->orWhereHas(
                        'teacher.user',
                        function (Builder $userQuery) use ($keyword): void {
                            $userQuery
                                ->whereLike('name', '%'.$keyword.'%')
                                ->orWhereLike('email', '%'.$keyword.'%');
                        },
                    );
            },
        );
    }

    /**
     * 生徒、教員、面談種別の検索条件を適用する。
     *
     * @param  Builder<InterviewRecord>  $query  面談記録クエリ
     * @param  int|null  $studentId  生徒ID
     * @param  int|null  $teacherId  教員ID
     * @param  string|null  $interviewType  面談種別
     * @return void 戻り値なし
     */
    private function applyExactFilters(
        Builder $query,
        ?int $studentId,
        ?int $teacherId,
        ?string $interviewType,
    ): void {
        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        if ($teacherId !== null) {
            $query->where('teacher_id', $teacherId);
        }

        if ($interviewType !== null) {
            $query->whereLike(
                'interview_type',
                '%'.$interviewType.'%',
            );
        }
    }
}
