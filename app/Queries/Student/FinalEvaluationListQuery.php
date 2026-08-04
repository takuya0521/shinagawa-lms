<?php

namespace App\Queries\Student;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Models\FinalEvaluation;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class FinalEvaluationListQuery
{
    /**
     * 本人の確定済み評価だけを返す。
     *
     * @param  Student  $student  対象生徒
     * @param  int  $academicYear  対象年度
     * @param  EvaluationTerm  $term  対象学期
     * @return LengthAwarePaginator<int, FinalEvaluation>
     */
    public function execute(
        Student $student,
        int $academicYear,
        EvaluationTerm $term,
    ): LengthAwarePaginator {
        return FinalEvaluation::query()
            ->select('final_evaluations.*')
            ->join(
                'courses',
                'courses.id',
                '=',
                'final_evaluations.course_id',
            )
            ->join(
                'subjects',
                'subjects.id',
                '=',
                'courses.subject_id',
            )
            ->with([
                'course.subject',
                'course.classGroup',
                'course.teacher.user',
            ])
            ->where('final_evaluations.student_id', $student->id)
            ->where('final_evaluations.academic_year', $academicYear)
            ->where('final_evaluations.term_name', $term->value)
            ->where(
                'final_evaluations.status',
                EvaluationStatus::Confirmed->value,
            )
            ->orderByDesc('final_evaluations.academic_year')
            ->orderBy('subjects.subject_code')
            ->orderBy('courses.course_name')
            ->paginate(20)
            ->withQueryString();
    }
}
