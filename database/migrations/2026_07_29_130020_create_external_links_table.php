<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Googleサービスなどへの外部リンクを保持するテーブルを作成する。
     */
    public function up(): void
    {
        Schema::create('external_links', function (Blueprint $table): void {
            $table->id();
            $table->string('link_type', 30);
            $table->string('link_name', 100);
            $table->string('url', 500);
            $table->string('scope_type', 30)->default('global');
            $table->bigInteger('scope_id')->nullable();
            $table->integer('display_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(
                ['status', 'link_type', 'scope_type', 'scope_id'],
                'idx_external_links_resolution',
            );
            $table->index(
                ['display_order', 'link_name'],
                'idx_external_links_display',
            );
        });

        // 公開対象IDと表示順の許容範囲をDB側でも保証する。
        DB::statement(
            'ALTER TABLE external_links ADD CONSTRAINT chk_external_links_scope_id '
                .'CHECK (scope_id IS NULL OR scope_id > 0)',
        );
        DB::statement(
            'ALTER TABLE external_links ADD CONSTRAINT chk_external_links_display_order '
                .'CHECK (display_order BETWEEN 0 AND 9999)',
        );
    }

    /**
     * 外部リンクテーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('external_links');
    }
};
