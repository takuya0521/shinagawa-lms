<?php

use App\Enums\LessonStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 時間割から発生する日別授業を作成する。
     */
    public function up(): void
    {
        Schema::create('lesson_sessions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('timetable_slot_id')
                ->constrained()
                ->restrictOnDelete();

            $table->date('lesson_date')
                ->comment('出欠対象日');

            $table->string('status', 20)
                ->default(LessonStatus::Scheduled->value)
                ->comment('状態: scheduled / completed / cancelled');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // 同じ時間割枠・日付の授業実施日を重複作成しない。
            $table->unique(
                [
                    'timetable_slot_id',
                    'lesson_date',
                ],
                'uq_lesson_slot_date',
            );

            $table->index(
                [
                    'lesson_date',
                    'status',
                ],
                'idx_lesson_date_status',
            );
        });
    }

    /**
     * 授業実施日テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_sessions');
    }
};
