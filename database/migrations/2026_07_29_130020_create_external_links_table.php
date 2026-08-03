<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->unsignedInteger('display_order')->default(0);
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
    }

    /**
     * 外部リンクテーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('external_links');
    }
};
