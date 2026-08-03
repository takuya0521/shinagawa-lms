<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 掲示板のお知らせを保持するテーブルを作成する。
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('notice_type', 30)->default('school');
            $table->boolean('is_important')->default(false);
            $table->dateTime('publish_start_at')->nullable();
            $table->dateTime('publish_end_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['status', 'publish_start_at', 'publish_end_at', 'deleted_at'],
                'idx_announcements_status_period',
            );
            $table->index(
                ['is_important', 'publish_start_at'],
                'idx_announcements_important_start',
            );
        });
    }

    /**
     * 掲示板テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
