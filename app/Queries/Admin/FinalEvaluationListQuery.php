<?php

namespace App\Queries\Admin;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\StudentStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final class FinalEvaluationListQuery
{
    /**
     * 管理者向けに評価済み・未入力を含む評価候補一覧を返す。
     *
     * @param  int  $academicYear  対象年度
     * @param  EvaluationTerm  $term  対象学期
     * @param  ?int  $studentId  対象生徒ID
     * @param  ?int  $courseId  対象授業ID
     * @param  ?int  $subjectId  対象データの識別子
     * @param  ?EvaluationStatus  $status  設定する状態
     * @param  bool  $missingOnly  未登録のみを対象とするフラグ
     * @return LengthAwarePaginator<int, object>
     */
    public function execute(
        int $academicYear,
        EvaluationTerm $term,
        ?int $studentId,
        ?int $courseId,
        ?int $subjectId,
        ?EvaluationStatus $status,
        bool $missingOnly,
    ): LengthAwarePaginator {
        return $this->baseQuery($term)
            ->where('courses.academic_year', $academicYear)
            ->when(
                $studentId !== null,
                static fn (Builder $query): Builder => $query->where(
                    'students.id',
                    $studentId,
                ),
            )
            ->when(
                $courseId !== null,
                static fn (Builder $query): Builder => $query->where(
                    'courses.id',
                    $courseId,
                ),
            )
            ->when(
                $subjectId !== null,
                static fn (Builder $query): Builder => $query->where(
                    'subjects.id',
                    $subjectId,
                ),
            )
            ->when(
                $status !== null,
                static fn (Builder $query): Builder => $query->where(
                    'final_evaluations.status',
                    $status->value,
                ),
            )
            ->when(
                $missingOnly,
                static fn (Builder $query): Builder => $query->whereNull(
                    'final_evaluations.id',
                ),
            )
            ->orderBy('courses.grade')
            ->orderBy('class_groups.class_code')
            ->orderBy('subjects.subject_code')
            ->orderByRaw('students.student_no IS NULL')
            ->orderBy('students.student_no')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * 評価対象候補を作る共通クエリを返す。
     *
     * @param  EvaluationTerm  $term  対象学期
     * @return Builder 処理結果
     */
    private function baseQuery(
        EvaluationTerm $term,
    ): Builder {
        return DB::table('courses')
            ->join(
                'students',
                static function (JoinClause $join): void {
                    $join
                        ->on(
                            'students.class_group_id',
                            '=',
                            'courses.class_group_id',
                        )
                        ->on(
                            'students.grade',
                            '=',
                            'courses.grade',
                        )
                        ->whereNull('students.deleted_at')
                        ->where(
                            'students.status',
                            StudentStatus::Active->value,
                        );
                },
            )
            ->join(
                'users as student_users',
                'student_users.id',
                '=',
                'students.user_id',
            )
            ->join(
                'subjects',
                'subjects.id',
                '=',
                'courses.subject_id',
            )
            ->join(
                'class_groups',
                'class_groups.id',
                '=',
                'courses.class_group_id',
            )
            ->leftJoin(
                'teachers',
                'teachers.id',
                '=',
                'courses.teacher_id',
            )
            ->leftJoin(
                'users as teacher_users',
                'teacher_users.id',
                '=',
                'teachers.user_id',
            )
            ->leftJoin(
                'final_evaluations',
                static function (JoinClause $join) use ($term): void {
                    $join
                        ->on(
                            'final_evaluations.student_id',
                            '=',
                            'students.id',
                        )
                        ->on(
                            'final_evaluations.course_id',
                            '=',
                            'courses.id',
                        )
                        ->on(
                            'final_evaluations.academic_year',
                            '=',
                            'courses.academic_year',
                        )
                        ->where(
                            'final_evaluations.term_name',
                            $term->value,
                        );
                },
            )
            ->whereNull('courses.deleted_at')
            ->select([
                'final_evaluations.id as evaluation_id',
                'final_evaluations.submission_score',
                'final_evaluations.attendance_score',
                'final_evaluations.attitude_score',
                'final_evaluations.total_score',
                'final_evaluations.grade_level',
                'final_evaluations.status as evaluation_status',
                'courses.id as course_id',
                'courses.academic_year',
                'courses.course_name',
                'courses.grade',
                'courses.google_classroom_url',
                'subjects.id as subject_id',
                'subjects.subject_code',
                'subjects.subject_name',
                'class_groups.id as class_group_id',
                'class_groups.class_code',
                'class_groups.class_name',
                'students.id as student_id',
                'students.student_no',
                'students.student_name',
                'student_users.email as student_email',
                'teacher_users.name as teacher_name',
            ]);
    }
}
