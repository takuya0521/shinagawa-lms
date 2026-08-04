<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Evaluation\SaveEvaluationBulkAction;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\EvaluationEntryRequest;
use App\Http\Requests\Teacher\SaveEvaluationRequest;
use App\Models\Course;
use App\Models\User;
use App\Queries\Evaluation\EvaluationEntryQuery;
use App\Services\EvaluationCalculator;
use App\Services\TeacherContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class EvaluationEntryController extends Controller
{
    /**
     * 担当授業の最終評価入力画面を表示する。
     *
     * @param  EvaluationEntryRequest  $request  HTTPリクエスト
     * @param  TeacherContextService  $teacherContextService  共通サービス
     * @param  EvaluationEntryQuery  $evaluationEntryQuery  データ取得処理
     * @param  EvaluationCalculator  $evaluationCalculator  評価計算サービス
     * @return View 表示する画面
     */
    public function edit(
        EvaluationEntryRequest $request,
        TeacherContextService $teacherContextService,
        EvaluationEntryQuery $evaluationEntryQuery,
        EvaluationCalculator $evaluationCalculator,
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

        $course = null;
        $rows = collect();

        if ($request->courseId() !== null) {
            $course = Course::query()
                ->with([
                    'subject',
                    'classGroup',
                ])
                ->findOrFail($request->courseId());
            $teacherContextService->assertAssignedCourse(
                $user,
                $course,
            );
            abort_unless(
                $course->status === MasterStatus::Active
                    && $course->academic_year === $academicYear,
                404,
            );

            $rows = $evaluationEntryQuery->execute(
                $course,
                $academicYear,
                $term,
            );
        }

        return view('teacher.evaluations.entry', [
            'courses' => $courses,
            'course' => $course,
            'rows' => $rows,
            'academicYear' => $academicYear,
            'term' => $term,
            'terms' => EvaluationTerm::cases(),
            'statuses' => EvaluationStatus::cases(),
            'gradingConfigured' => $evaluationCalculator->isGradingConfigured(),
            'evaluationPreviewConfig' => $evaluationCalculator->previewConfiguration(),
        ]);
    }

    /**
     * 担当授業の最終評価を一括保存する。
     *
     * @param  SaveEvaluationRequest  $request  HTTPリクエスト
     * @param  TeacherContextService  $teacherContextService  共通サービス
     * @param  SaveEvaluationBulkAction  $saveEvaluationBulkAction  業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        SaveEvaluationRequest $request,
        TeacherContextService $teacherContextService,
        SaveEvaluationBulkAction $saveEvaluationBulkAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $course = Course::query()->findOrFail(
            $request->integer('course_id'),
        );
        $teacherContextService->assertAssignedCourse(
            $user,
            $course,
        );
        abort_unless(
            $course->status === MasterStatus::Active,
            404,
        );

        $result = $saveEvaluationBulkAction->execute(
            $course,
            $request->integer('academic_year'),
            $request->term(),
            $request->status(),
            $request->rows(),
            $user,
        );

        return redirect()
            ->route('teacher.evaluations.index', [
                'academic_year' => $request->integer('academic_year'),
                'term_name' => $request->term()->value,
                'course_id' => $course->id,
            ])
            ->with(
                'status',
                "評価を{$result->savedCount}件保存しました。",
            );
    }
}
