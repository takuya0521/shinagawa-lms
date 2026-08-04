<?php

namespace Tests\Feature;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Models\FinalEvaluation;
use App\Models\OperationLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 最終評価・操作ログモデルの関連、型変換、一意制約を確認するテスト。
 *
 * Eloquentリレーション、重複登録防止、JSON詳細の配列変換を検証する。
 */
final class EvaluationModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 最終評価モデルのリレーションと属性キャストが利用できることを確認する。
     *
     * 前提: 最終評価など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致する、対象条件が真になることを確認する。
     */
    public function test_final_evaluation_relations_and_casts_are_available(): void
    {
        $evaluation = FinalEvaluation::factory()->create([
            'term_name' => EvaluationTerm::Annual,
            'status' => EvaluationStatus::Confirmed,
            'grade_level' => 4,
        ]);

        $this->assertTrue($evaluation->student->is($evaluation->student));
        $this->assertTrue($evaluation->course->is($evaluation->course));
        $this->assertTrue($evaluation->evaluator->is($evaluation->evaluator));
        $this->assertSame(EvaluationTerm::Annual, $evaluation->term_name);
        $this->assertSame(EvaluationStatus::Confirmed, $evaluation->status);
        $this->assertSame(4, $evaluation->grade_level);
    }

    /**
     * 同じ生徒・授業・年度・学期の最終評価を重複登録できないことを確認する。
     *
     * 前提: 最終評価など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した例外または制約違反が発生することを確認する。
     */
    public function test_same_student_course_year_and_term_cannot_be_duplicated(): void
    {
        $evaluation = FinalEvaluation::factory()->create();

        $this->expectException(QueryException::class);

        FinalEvaluation::factory()->create([
            'student_id' => $evaluation->student_id,
            'course_id' => $evaluation->course_id,
            'academic_year' => $evaluation->academic_year,
            'term_name' => $evaluation->term_name,
        ]);
    }

    /**
     * 操作ログの詳細JSONが配列へキャストされることを確認する。
     *
     * 前提: 操作ログなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致する、対象値が設定されることを確認する。
     */
    public function test_operation_log_casts_detail_to_array(): void
    {
        $log = OperationLog::factory()->create([
            'detail' => [
                'correction_reason' => '入力誤りのため',
                'before' => ['total_score' => '70.00'],
                'after' => ['total_score' => '80.00'],
            ],
        ]);

        $this->assertSame(
            '入力誤りのため',
            $log->detail['correction_reason'],
        );
        $this->assertNotNull($log->created_at);
    }
}
