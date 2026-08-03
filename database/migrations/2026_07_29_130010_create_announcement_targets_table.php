<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * お知らせの公開対象を保持するテーブルを作成する。
     */
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')
                ->constrained('announcements')
                ->cascadeOnDelete();
            $table->string('target_type', 30);
            $table->string('target_value', 100)->nullable();
            $table->timestamps();

            $table->unique(
                ['announcement_id', 'target_type', 'target_value'],
                'uq_announcement_target',
            );
            $table->index(
                ['target_type', 'target_value'],
                'idx_announcement_target_lookup',
            );
        });
    }

    /**
     * お知らせ対象テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};
