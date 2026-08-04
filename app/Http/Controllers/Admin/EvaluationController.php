<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationIndexRequest;
use App\Models\Course;
use App\Models\Student;
use App\Models\Subject;
use App\Queries\Admin\FinalEvaluationListQuery;
use Illuminate\View\View;

final class EvaluationController extends Controller
{
    /**
     * 全生徒・全授業の評価一覧を表示する。
     *
     * @param  EvaluationIndexRequest  $request  HTTPリクエスト
     * @param  FinalEvaluationListQuery  $finalEvaluationListQuery  データ取得処理
     * @return View 表示する画面
     */
    public function index(
        EvaluationIndexRequest $request,
        FinalEvaluationListQuery $finalEvaluationListQuery,
    ): View {
        $academicYear = $request->academicYear();
        $term = $request->term();
        $courses = Course::query()
            ->with([
                'subject',
                'classGroup',
            ])
            ->orderByDesc('academic_year')
            ->orderBy('course_name')
            ->get();

        return view('admin.evaluations.index', [
            'evaluations' => $finalEvaluationListQuery->execute(
                $academicYear,
                $term,
                $request->nullableId('student_id'),
                $request->nullableId('course_id'),
                $request->nullableId('subject_id'),
                $request->evaluationStatus(),
                $request->missingOnly(),
            ),
            'academicYears' => $courses
                ->pluck('academic_year')
                ->push($academicYear)
                ->unique()
                ->sortDesc()
                ->values(),
            'terms' => EvaluationTerm::cases(),
            'statuses' => EvaluationStatus::cases(),
            'courses' => $courses,
            'subjects' => Subject::query()
                ->orderBy('subject_code')
                ->get(),
            'students' => Student::query()
                ->orderByRaw('student_no IS NULL')
                ->orderBy('student_no')
                ->orderBy('student_name')
                ->get(),
            'academicYear' => $academicYear,
            'term' => $term,
            'selectedStudentId' => $request->nullableId('student_id'),
            'selectedCourseId' => $request->nullableId('course_id'),
            'selectedSubjectId' => $request->nullableId('subject_id'),
            'selectedStatus' => $request->evaluationStatus(),
            'missingOnly' => $request->missingOnly(),
        ]);
    }
}
