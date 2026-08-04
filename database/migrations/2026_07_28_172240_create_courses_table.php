<?php

use App\Enums\MasterStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 年度・学年・クラス単位の授業を作成する。
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('subject_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('class_group_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('grade', 20)
                ->comment('対象学年コード: 1 / 2 / 3');

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('course_name', 100)
                ->comment('授業名');

            $table->smallInteger('academic_year')
                ->comment('年度');

            $table->string('google_classroom_url', 500)
                ->nullable()
                ->comment('Google Classroom URL');

            $table->string('google_classroom_id', 100)
                ->nullable()
                ->comment('Google Classroom外部ID');

            $table->string('status', 20)
                ->default(MasterStatus::Active->value)
                ->comment('状態: active / inactive');

            $table->timestamps();
            $table->softDeletes();

            // 同一年度・クラス・学年内で同じ授業を重複登録しない。
            $table->unique(
                [
                    'academic_year',
                    'class_group_id',
                    'grade',
                    'subject_id',
                    'course_name',
                ],
                'uq_courses_year_class_grade_subject_name',
            );

            $table->index(
                [
                    'academic_year',
                    'grade',
                    'class_group_id',
                    'status',
                ],
                'idx_courses_year_grade_class',
            );

            $table->index(
                [
                    'teacher_id',
                    'academic_year',
                    'status',
                ],
                'idx_courses_teacher_year',
            );
        });

        // 画面入力規則と同じ年度範囲をDB側でも保証する。
        DB::statement(
            'ALTER TABLE courses ADD CONSTRAINT chk_courses_academic_year CHECK (academic_year BETWEEN 2000 AND 2100)',
        );
    }

    /**
     * 授業テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
