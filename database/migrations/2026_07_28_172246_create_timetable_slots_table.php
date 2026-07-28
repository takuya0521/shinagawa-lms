<?php

use App\Enums\MasterStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 通年固定の時間割枠を作成する。
     */
    public function up(): void
    {
        Schema::create('timetable_slots', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('course_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('day_of_week')
                ->comment('曜日: 1=月曜日〜7=日曜日');

            $table->unsignedTinyInteger('period_no')
                ->comment('時限');

            $table->time('start_time')
                ->nullable()
                ->comment('開始時刻');

            $table->time('end_time')
                ->nullable()
                ->comment('終了時刻');

            $table->string('status', 20)
                ->default(MasterStatus::Active->value)
                ->comment('状態: active / inactive');

            $table->timestamps();

            // 同一授業の同一曜日・時限を重複登録しない。
            $table->unique(
                [
                    'course_id',
                    'day_of_week',
                    'period_no',
                ],
                'uq_timetable_course_day_period',
            );

            $table->index(
                [
                    'day_of_week',
                    'period_no',
                    'status',
                ],
                'idx_timetable_day_period',
            );
        });
    }

    /**
     * 時間割枠テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
