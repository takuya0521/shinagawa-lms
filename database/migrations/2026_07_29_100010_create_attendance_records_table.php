<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 生徒ごとの出欠記録を作成する。
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lesson_session_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('attendance_status', 20)
                ->comment('出欠区分: present / absent / late / early_leave');

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('corrected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('note', 255)
                ->nullable()
                ->comment('備考・管理者修正理由');

            $table->timestamps();

            // 同じ授業実施日に同じ生徒の出欠を重複登録しない。
            $table->unique(
                [
                    'lesson_session_id',
                    'student_id',
                ],
                'uq_attendance_session_student',
            );

            $table->index(
                [
                    'student_id',
                    'lesson_session_id',
                ],
                'idx_attendance_student_session',
            );

            $table->index(
                'attendance_status',
                'idx_attendance_status',
            );
        });
    }

    /**
     * 出欠記録テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
