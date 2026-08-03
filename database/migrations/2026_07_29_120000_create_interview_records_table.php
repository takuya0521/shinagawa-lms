<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 生徒ごとの面談履歴を作成する。
     */
    public function up(): void
    {
        Schema::create('interview_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('interview_date');

            $table->string('interview_type', 30)
                ->nullable()
                ->comment('面談種別: 定期 / 希望 / 進路など');

            $table->text('memo')
                ->nullable()
                ->comment('LMSに保持する簡易メモ');

            $table->text('next_action')
                ->nullable()
                ->comment('次回対応事項');

            $table->string('drive_url', 500)
                ->nullable()
                ->comment('詳細議事録・添付資料へのGoogle Driveリンク');

            $table->string('meet_url', 500)
                ->nullable()
                ->comment('オンライン面談用Google Meetリンク');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                [
                    'interview_date',
                    'student_id',
                ],
                'idx_interviews_date_student',
            );

            $table->index(
                [
                    'teacher_id',
                    'interview_date',
                ],
                'idx_interviews_teacher_date',
            );
        });
    }

    /**
     * 面談記録テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_records');
    }
};
