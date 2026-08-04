<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CorrectFinalEvaluationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CorrectFinalEvaluationRequest;
use App\Models\FinalEvaluation;
use App\Models\User;
use App\Services\EvaluationCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class EvaluationCorrectionController extends Controller
{
    /**
     * 生徒別成績詳細・修正画面を表示する。
     *
     * @param  FinalEvaluation  $finalEvaluation  対象の最終評価
     * @param  EvaluationCalculator  $evaluationCalculator  評価計算サービス
     * @return View 表示する画面
     */
    public function edit(
        FinalEvaluation $finalEvaluation,
        EvaluationCalculator $evaluationCalculator,
    ): View {
        $finalEvaluation->load([
            'student.user',
            'student.classGroup',
            'course.subject',
            'course.teacher.user',
            'evaluator',
        ]);

        return view('admin.evaluations.edit', [
            'finalEvaluation' => $finalEvaluation,
            'gradingConfigured' => $evaluationCalculator->isGradingConfigured(),
            'evaluationPreviewConfig' => $evaluationCalculator->previewConfiguration(),
        ]);
    }

    /**
     * 管理者による評価修正を保存する。
     *
     * @param  CorrectFinalEvaluationRequest  $request  HTTPリクエスト
     * @param  FinalEvaluation  $finalEvaluation  対象の最終評価
     * @param  CorrectFinalEvaluationAction  $correctFinalEvaluationAction  業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        CorrectFinalEvaluationRequest $request,
        FinalEvaluation $finalEvaluation,
        CorrectFinalEvaluationAction $correctFinalEvaluationAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $correctFinalEvaluationAction->execute(
            $finalEvaluation,
            $request->correctionAttributes(),
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.evaluations.index', [
                'academic_year' => $finalEvaluation->academic_year,
                'term_name' => $finalEvaluation->term_name->value,
                'student_id' => $finalEvaluation->student_id,
            ])
            ->with(
                'status',
                '評価を修正し、操作ログへ記録しました。',
            );
    }
}
