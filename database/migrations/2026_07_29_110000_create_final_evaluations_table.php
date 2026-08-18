<?php

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

            $table->smallInteger('academic_year');

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

            $table->smallInteger('grade_level')
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

        // 評価年度、各得点、5段階評価の許容範囲をDB側でも保証する。
        DB::statement(
            'ALTER TABLE final_evaluations ADD CONSTRAINT chk_evaluations_academic_year '
                .'CHECK (academic_year BETWEEN 2000 AND 2100)',
        );
        DB::statement(
            'ALTER TABLE final_evaluations ADD CONSTRAINT chk_evaluations_submission_score '
                .'CHECK (submission_score BETWEEN 0 AND 100)',
        );
        DB::statement(
            'ALTER TABLE final_evaluations ADD CONSTRAINT chk_evaluations_attendance_score '
                .'CHECK (attendance_score BETWEEN 0 AND 100)',
        );
        DB::statement(
            'ALTER TABLE final_evaluations ADD CONSTRAINT chk_evaluations_attitude_score '
                .'CHECK (attitude_score BETWEEN 0 AND 100)',
        );
        DB::statement(
            'ALTER TABLE final_evaluations ADD CONSTRAINT chk_evaluations_total_score '
                .'CHECK (total_score BETWEEN 0 AND 100)',
        );
        DB::statement(
            'ALTER TABLE final_evaluations ADD CONSTRAINT chk_evaluations_grade_level '
                .'CHECK (grade_level IS NULL OR grade_level BETWEEN 1 AND 5)',
        );
    }

    /**
     * 最終評価テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('final_evaluations');
    }
};
