<?php

namespace App\Actions\Evaluation;

use App\Data\Evaluation\EvaluationBulkSaveResult;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\FinalEvaluation;
use App\Models\Student;
use App\Models\User;
use App\Queries\Attendance\TargetStudentQuery;
use App\Queries\Evaluation\AttendanceScoreQuery;
use App\Services\EvaluationCalculator;
use App\Support\Evaluation\AttendanceScoreResult;
use App\Support\Evaluation\EvaluationCalculation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 担当授業の対象生徒全員について最終評価を一括保存する。
 */
final class SaveEvaluationBulkAction
{
    /**
     * 最終評価一括保存処理を生成する。
     *
     * @param  TargetStudentQuery  $targetStudentQuery  授業の評価対象生徒を取得するQuery
     * @param  AttendanceScoreQuery  $attendanceScoreQuery  生徒ごとの出欠点を算出するQuery
     * @param  EvaluationCalculator  $evaluationCalculator  最終評価を計算するサービス
     */
    public function __construct(
        private readonly TargetStudentQuery $targetStudentQuery,
        private readonly AttendanceScoreQuery $attendanceScoreQuery,
        private readonly EvaluationCalculator $evaluationCalculator,
    ) {}

    /**
     * 担当授業の対象生徒全員について最終評価を一括保存する。
     *
     * @param  Course  $course  保存対象の授業
     * @param  int  $academicYear  評価年度
     * @param  EvaluationTerm  $term  評価学期
     * @param  EvaluationStatus  $status  保存する評価状態
     * @param  list<array{student_id: int, submission_score: float, attitude_score: float}>  $rows  評価入力一覧
     * @param  User  $user  保存を実行する教員
     * @return EvaluationBulkSaveResult 保存結果
     *
     * @throws ValidationException 授業または評価入力が保存条件を満たさない場合
     */
    public function execute(
        Course $course,
        int $academicYear,
        EvaluationTerm $term,
        EvaluationStatus $status,
        array $rows,
        User $user,
    ): EvaluationBulkSaveResult {
        return DB::transaction(function () use (
            $course,
            $academicYear,
            $term,
            $status,
            $rows,
            $user,
        ): EvaluationBulkSaveResult {
            $lockedCourse = $this->lockCourse($course);
            $this->assertCourseCanBeEvaluated(
                $lockedCourse,
                $academicYear,
            );

            $students = $this->targetStudentQuery->execute($lockedCourse);
            $this->assertSubmittedStudentsMatch($students, $rows);

            $existingEvaluations = $this->lockExistingEvaluations(
                $lockedCourse,
                $academicYear,
                $term,
                $students,
            );
            $attendanceScores = $this->attendanceScoreQuery->execute(
                $lockedCourse,
                $students,
            );

            $this->saveRows(
                $lockedCourse,
                $academicYear,
                $term,
                $status,
                $rows,
                $user,
                $existingEvaluations,
                $attendanceScores,
            );

            return new EvaluationBulkSaveResult(
                course: $lockedCourse,
                academicYear: $academicYear,
                term: $term,
                status: $status,
                savedCount: count($rows),
            );
        });
    }

    /**
     * 授業を排他ロックして返す。
     *
     * @param  Course  $course  保存対象の授業
     * @return Course 排他ロック済みの授業
     */
    private function lockCourse(Course $course): Course
    {
        return Course::query()
            ->lockForUpdate()
            ->findOrFail($course->id);
    }

    /**
     * 授業が評価対象として有効で、評価年度と一致することを確認する。
     *
     * @param  Course  $course  排他ロック済みの授業
     * @param  int  $academicYear  評価年度
     * @return void 戻り値なし
     *
     * @throws ValidationException 評価対象として利用できない場合
     */
    private function assertCourseCanBeEvaluated(
        Course $course,
        int $academicYear,
    ): void {
        if (
            $course->status === MasterStatus::Active
            && $course->academic_year === $academicYear
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'academic_year' => '有効な担当授業の年度と評価年度が一致していることを確認してください。',
        ]);
    }

    /**
     * 送信された生徒一覧が授業の評価対象生徒一覧と一致することを確認する。
     *
     * @param  Collection<int, Student>  $students  評価対象生徒一覧
     * @param  list<array{student_id: int, submission_score: float, attitude_score: float}>  $rows  評価入力一覧
     * @return void 戻り値なし
     *
     * @throws ValidationException 生徒一覧が一致しない場合
     */
    private function assertSubmittedStudentsMatch(
        Collection $students,
        array $rows,
    ): void {
        $targetStudentIds = $students
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $submittedStudentIds = collect($rows)
            ->pluck('student_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($targetStudentIds !== $submittedStudentIds) {
            throw ValidationException::withMessages([
                'evaluations' => '評価対象の学年またはクラスが一致しません。画面を再読み込みしてください。',
            ]);
        }
    }

    /**
     * 既存の最終評価を排他ロックして生徒IDごとに返す。
     *
     * @param  Course  $course  保存対象の授業
     * @param  int  $academicYear  評価年度
     * @param  EvaluationTerm  $term  評価学期
     * @param  Collection<int, Student>  $students  評価対象生徒一覧
     * @return Collection<int, FinalEvaluation> 生徒IDをキーにした既存評価一覧
     */
    private function lockExistingEvaluations(
        Course $course,
        int $academicYear,
        EvaluationTerm $term,
        Collection $students,
    ): Collection {
        return FinalEvaluation::query()
            ->where('course_id', $course->id)
            ->where('academic_year', $academicYear)
            ->where('term_name', $term->value)
            ->whereIn('student_id', $students->modelKeys())
            ->lockForUpdate()
            ->get()
            ->keyBy('student_id');
    }

    /**
     * 評価入力一覧を保存する。
     *
     * @param  Course  $course  保存対象の授業
     * @param  int  $academicYear  評価年度
     * @param  EvaluationTerm  $term  評価学期
     * @param  EvaluationStatus  $status  保存する評価状態
     * @param  list<array{student_id: int, submission_score: float, attitude_score: float}>  $rows  評価入力一覧
     * @param  User  $user  保存を実行する教員
     * @param  Collection<int, FinalEvaluation>  $existingEvaluations  生徒IDをキーにした既存評価一覧
     * @param  array<int, AttendanceScoreResult>  $attendanceScores  生徒IDごとの出欠点算出結果
     * @return void 戻り値なし
     *
     * @throws ValidationException 評価を確定できない場合
     */
    private function saveRows(
        Course $course,
        int $academicYear,
        EvaluationTerm $term,
        EvaluationStatus $status,
        array $rows,
        User $user,
        Collection $existingEvaluations,
        array $attendanceScores,
    ): void {
        foreach ($rows as $index => $row) {
            $calculation = $this->evaluationCalculator->calculate(
                $row['submission_score'],
                $attendanceScores[$row['student_id']],
                $row['attitude_score'],
            );

            $this->assertEvaluationCanBeSaved(
                $status,
                $calculation,
                $index,
            );

            $existing = $existingEvaluations->get($row['student_id']);
            $existingEvaluation = $existing instanceof FinalEvaluation
                ? $existing
                : null;

            $this->saveEvaluation(
                $course,
                $academicYear,
                $term,
                $status,
                $row,
                $user,
                $existingEvaluation,
                $calculation,
            );
        }
    }

    /**
     * 評価確定時に計算結果が確定条件を満たすことを確認する。
     *
     * @param  EvaluationStatus  $status  保存する評価状態
     * @param  EvaluationCalculation  $calculation  評価計算結果
     * @param  int  $rowIndex  評価入力一覧内の行番号
     * @return void 戻り値なし
     *
     * @throws ValidationException 評価を確定できない場合
     */
    private function assertEvaluationCanBeSaved(
        EvaluationStatus $status,
        EvaluationCalculation $calculation,
        int $rowIndex,
    ): void {
        if (
            $status !== EvaluationStatus::Confirmed
            || $calculation->canConfirm()
        ) {
            return;
        }

        throw ValidationException::withMessages([
            "evaluations.{$rowIndex}.submission_score" => $calculation->warnings[0]
                ?? '評価を確定できません。評価設定と出欠情報を確認してください。',
        ]);
    }

    /**
     * 生徒1名分の最終評価を登録または更新する。
     *
     * @param  Course  $course  保存対象の授業
     * @param  int  $academicYear  評価年度
     * @param  EvaluationTerm  $term  評価学期
     * @param  EvaluationStatus  $status  保存する評価状態
     * @param  array{student_id: int, submission_score: float, attitude_score: float}  $row  評価入力
     * @param  User  $user  保存を実行する教員
     * @param  FinalEvaluation|null  $existingEvaluation  既存の最終評価
     * @param  EvaluationCalculation  $calculation  評価計算結果
     * @return void 戻り値なし
     */
    private function saveEvaluation(
        Course $course,
        int $academicYear,
        EvaluationTerm $term,
        EvaluationStatus $status,
        array $row,
        User $user,
        ?FinalEvaluation $existingEvaluation,
        EvaluationCalculation $calculation,
    ): void {
        $existingAttendanceScore = $existingEvaluation === null
            ? 0.0
            : (float) $existingEvaluation->attendance_score;
        $existingTotalScore = $existingEvaluation === null
            ? 0.0
            : (float) $existingEvaluation->total_score;

        FinalEvaluation::query()->updateOrCreate(
            [
                'student_id' => $row['student_id'],
                'course_id' => $course->id,
                'academic_year' => $academicYear,
                'term_name' => $term->value,
            ],
            [
                'submission_score' => $row['submission_score'],
                'attendance_score' => $calculation->attendance->score
                    ?? $existingAttendanceScore,
                'attitude_score' => $row['attitude_score'],
                'total_score' => $calculation->totalScore
                    ?? $existingTotalScore,
                'grade_level' => $calculation->gradeLevel,
                'evaluated_by' => $user->id,
                'status' => $status->value,
            ],
        );
    }
}
