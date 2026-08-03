<?php

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 生徒・授業・年度・期間ごとの最終評価を作成する。
     */
    public function up(): void
    {
        Schema::create('final_evaluations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('course_id')
                ->constrained()
                ->restrictOnDelete();

            $table->year('academic_year');

            $table->string('term_name', 30)
                ->default(EvaluationTerm::Annual->value)
                ->comment('評価期間: annual');

            $table->decimal('submission_score', 5, 2)
                ->default(0)
                ->comment('提出物点');

            $table->decimal('attendance_score', 5, 2)
                ->default(0)
                ->comment('出欠点');

            $table->decimal('attitude_score', 5, 2)
                ->default(0)
                ->comment('授業態度点');

            $table->decimal('total_score', 5, 2)
                ->default(0)
                ->comment('総合点');

            $table->unsignedTinyInteger('grade_level')
                ->nullable()
                ->comment('5段階評価');

            $table->foreignId('evaluated_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('status', 20)
                ->default(EvaluationStatus::Draft->value)
                ->comment('状態: draft / confirmed');

            $table->timestamps();

            // 同一授業・生徒・年度・期間の評価を重複登録しない。
            $table->unique(
                [
                    'student_id',
                    'course_id',
                    'academic_year',
                    'term_name',
                ],
                'uq_evaluation_target_period',
            );

            $table->index(
                [
                    'course_id',
                    'academic_year',
                    'term_name',
                    'status',
                ],
                'idx_evaluations_course_year_term',
            );

            $table->index(
                [
                    'student_id',
                    'academic_year',
                    'term_name',
                    'status',
                ],
                'idx_evaluations_student_year',
            );
        });
    }

    /**
     * 最終評価テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('final_evaluations');
    }
};
