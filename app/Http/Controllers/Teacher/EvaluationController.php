<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\EvaluationIndexRequest;
use App\Models\Course;
use App\Models\User;
use App\Queries\Teacher\FinalEvaluationListQuery;
use App\Services\TeacherContextService;
use Illuminate\View\View;

final class EvaluationController extends Controller
{
    /**
     * 担当授業の評価一覧を表示する。
     *
     * @param EvaluationIndexRequest $request HTTPリクエスト
     * @param TeacherContextService $teacherContextService 共通サービス
     * @param FinalEvaluationListQuery $finalEvaluationListQuery データ取得処理
     * @return View 表示する画面
     */
    public function index(
        EvaluationIndexRequest $request,
        TeacherContextService $teacherContextService,
        FinalEvaluationListQuery $finalEvaluationListQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $academicYear = $request->academicYear();
        $term = $request->term();
        $courses = Course::query()
            ->with([
                'subject',
                'classGroup',
            ])
            ->where('teacher_id', $teacher->id)
            ->where('status', MasterStatus::Active->value)
            ->orderByDesc('academic_year')
            ->orderBy('course_name')
            ->get();

        return view('teacher.evaluations.index', [
            'evaluations' => $finalEvaluationListQuery->execute(
                $teacher,
                $academicYear,
                $term,
                $request->courseId(),
                $request->evaluationStatus(),
                $request->missingOnly(),
            ),
            'courses' => $courses,
            'academicYears' => $courses
                ->pluck('academic_year')
                ->push($academicYear)
                ->unique()
                ->sortDesc()
                ->values(),
            'terms' => EvaluationTerm::cases(),
            'statuses' => EvaluationStatus::cases(),
            'academicYear' => $academicYear,
            'term' => $term,
            'selectedCourseId' => $request->courseId(),
            'selectedStatus' => $request->evaluationStatus(),
            'missingOnly' => $request->missingOnly(),
        ]);
    }
}
