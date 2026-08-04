<?php

namespace App\Queries\Admin;

use App\Enums\EvaluationStatus;
use App\Models\FinalEvaluation;
use App\Models\InterviewRecord;
use App\Models\Student;
use Illuminate\Support\Collection;

final class StudentDetailQuery
{
    /**
     * 生徒詳細画面に表示する直近情報を取得する。
     *
     * @param  Student  $student  対象生徒
     * @return array{
     *     recentEvaluations: Collection<int, FinalEvaluation>,
     *     recentInterviews: Collection<int, InterviewRecord>
     * }
     */
    public function execute(Student $student): array
    {
        return [
            'recentEvaluations' => FinalEvaluation::query()
                ->with('course.subject')
                ->where('student_id', $student->id)
                ->orderByRaw(
                    'CASE WHEN status = ? THEN 0 ELSE 1 END',
                    [EvaluationStatus::Confirmed->value],
                )
                ->orderByDesc('academic_year')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get(),
            'recentInterviews' => InterviewRecord::query()
                ->with([
                    'teacher.user',
                    'creator',
                ])
                ->where('student_id', $student->id)
                ->orderByDesc('interview_date')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ];
    }
}
