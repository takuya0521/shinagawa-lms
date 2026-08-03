<?php

use App\Enums\StudentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 生徒基本情報を作成する。
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();

            // 1つのログインユーザーへ複数の生徒情報を紐付けない。
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            // 学校側で番号を使用しない場合を考慮してNULLを許可する。
            $table->string('student_no', 50)
                ->nullable()
                ->unique()
                ->comment('生徒番号');

            $table->string('student_name', 100)
                ->comment('氏名');

            // 学年コードは1・2・3のいずれかを保持する。
            $table->string('grade', 20)
                ->comment('学年');

            $table->string('affiliation', 100)
                ->nullable()
                ->comment('所属');

            $table->string('partner_school', 100)
                ->nullable()
                ->comment('提携校');

            $table->foreignId('class_group_id')
                ->constrained('class_groups')
                ->restrictOnDelete();

            $table->string('status', 20)
                ->default(StudentStatus::Active->value)
                ->comment(
                    '状態: active / suspended / graduated / withdrawn',
                );

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['grade', 'class_group_id', 'status'],
                'idx_students_grade_class_status',
            );

            $table->index(
                'student_name',
                'idx_students_name',
            );
        });
    }

    /**
     * 生徒基本情報を削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
