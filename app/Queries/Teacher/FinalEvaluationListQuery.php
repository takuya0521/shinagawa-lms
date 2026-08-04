<?php

namespace App\Queries\Teacher;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * 教員向け最終評価候補一覧を検索する。
 */
final class FinalEvaluationListQuery
{
    /**
     * ログイン教員の担当授業に限定した評価候補一覧を返す。
     *
     * @param  Teacher  $teacher  ログインユーザーに紐付く教員
     * @param  int  $academicYear  評価年度
     * @param  EvaluationTerm  $term  評価学期
     * @param  int|null  $courseId  授業ID
     * @param  EvaluationStatus|null  $status  評価状態
     * @param  bool  $missingOnly  未登録評価だけへ絞り込むか
     * @return LengthAwarePaginator<int, object> 評価候補一覧
     */
    public function execute(
        Teacher $teacher,
        int $academicYear,
        EvaluationTerm $term,
        ?int $courseId,
        ?EvaluationStatus $status,
        bool $missingOnly,
    ): LengthAwarePaginator {
        $query = $this->baseQuery($teacher, $academicYear, $term);
        $this->applyFilters($query, $courseId, $status, $missingOnly);

        return $query
            ->select($this->selectColumns())
            ->orderBy('courses.course_name')
            ->orderByRaw('students.student_no IS NULL')
            ->orderBy('students.student_no')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * 担当授業、生徒、科目、クラス、評価を結合した基礎クエリを作成する。
     *
     * @param  Teacher  $teacher  対象教員
     * @param  int  $academicYear  評価年度
     * @param  EvaluationTerm  $term  評価学期
     * @return Builder 評価候補の基礎クエリ
     */
    private function baseQuery(
        Teacher $teacher,
        int $academicYear,
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
            ->join('subjects', 'subjects.id', '=', 'courses.subject_id')
            ->join(
                'class_groups',
                'class_groups.id',
                '=',
                'courses.class_group_id',
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
            ->where('courses.teacher_id', $teacher->id)
            ->where('courses.status', MasterStatus::Active->value)
            ->where('courses.academic_year', $academicYear);
    }

    /**
     * 任意の授業・状態・未登録条件を適用する。
     *
     * @param  Builder  $query  評価候補の基礎クエリ
     * @param  int|null  $courseId  授業ID
     * @param  EvaluationStatus|null  $status  評価状態
     * @param  bool  $missingOnly  未登録評価だけへ絞り込むか
     * @return void 戻り値なし
     */
    private function applyFilters(
        Builder $query,
        ?int $courseId,
        ?EvaluationStatus $status,
        bool $missingOnly,
    ): void {
        if ($courseId !== null) {
            $query->where('courses.id', $courseId);
        }

        if ($status !== null) {
            $query->where('final_evaluations.status', $status->value);
        }

        if ($missingOnly) {
            $query->whereNull('final_evaluations.id');
        }
    }

    /**
     * 一覧画面で使用する取得カラムを返す。
     *
     * @return list<string> 取得カラム一覧
     */
    private function selectColumns(): array
    {
        return [
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
            'subjects.subject_code',
            'subjects.subject_name',
            'class_groups.class_code',
            'class_groups.class_name',
            'students.id as student_id',
            'students.student_no',
            'students.student_name',
        ];
    }
}
