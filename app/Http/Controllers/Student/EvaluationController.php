<?php

namespace App\Http\Controllers\Student;

use App\Enums\EvaluationTerm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EvaluationIndexRequest;
use App\Models\FinalEvaluation;
use App\Models\Student;
use App\Models\User;
use App\Queries\Student\FinalEvaluationListQuery;
use Illuminate\View\View;

final class EvaluationController extends Controller
{
    /**
     * 本人の確定済み成績一覧を表示する。
     *
     * @param  EvaluationIndexRequest  $request  HTTPリクエスト
     * @param  FinalEvaluationListQuery  $finalEvaluationListQuery  データ取得処理
     * @return View 表示する画面
     */
    public function index(
        EvaluationIndexRequest $request,
        FinalEvaluationListQuery $finalEvaluationListQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $student = Student::query()
            ->where('user_id', $user->id)
            ->first();
        abort_if($student === null, 403);

        $academicYear = $request->academicYear();
        $term = $request->term();
        $academicYears = FinalEvaluation::query()
            ->where('student_id', $student->id)
            ->select('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->map(static fn (mixed $year): int => (int) $year)
            ->push($academicYear)
            ->unique()
            ->sortDesc()
            ->values();

        return view('student.evaluations.index', [
            'evaluations' => $finalEvaluationListQuery->execute(
                $student,
                $academicYear,
                $term,
            ),
            'academicYears' => $academicYears,
            'terms' => EvaluationTerm::cases(),
            'academicYear' => $academicYear,
            'term' => $term,
        ]);
    }
}
