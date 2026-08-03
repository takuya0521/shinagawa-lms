<?php

namespace Tests\Feature;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Models\FinalEvaluation;
use App\Models\OperationLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EvaluationModelTest extends TestCase
{
    use RefreshDatabase;

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
