<?php

use App\Enums\MasterStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 科目マスタを作成する。
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();

            $table->string('subject_code', 30)
                ->unique()
                ->comment('科目コード');

            $table->string('subject_name', 100)
                ->comment('科目名');

            $table->string('status', 20)
                ->default(MasterStatus::Active->value)
                ->comment('状態: active / inactive');

            $table->timestamps();

            $table->index(
                'status',
                'idx_subjects_status',
            );
        });
    }

    /**
     * 科目マスタを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
