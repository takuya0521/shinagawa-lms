<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 管理者修正などの監査ログを作成する。
     */
    public function up(): void
    {
        Schema::create('operation_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('action', 100);
            $table->string('target_table', 100)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->jsonb('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                [
                    'user_id',
                    'created_at',
                ],
                'idx_logs_user_created',
            );

            $table->index(
                [
                    'target_table',
                    'target_id',
                    'created_at',
                ],
                'idx_logs_target',
            );
        });

        // 対象IDは業務テーブルの主キーを参照するため、正の値だけを許可する。
        DB::statement(
            'ALTER TABLE operation_logs ADD CONSTRAINT chk_operation_logs_target_id '
                .'CHECK (target_id IS NULL OR target_id > 0)',
        );
    }

    /**
     * 操作ログテーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};
