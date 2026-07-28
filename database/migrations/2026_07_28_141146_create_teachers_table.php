<?php

use App\Enums\MasterStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 教員固有情報を保持するテーブルを作成する。
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table): void {
            $table->id();

            // 1つのログインユーザーへ複数の教員情報を紐付けない。
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->text('subject_notes')
                ->nullable()
                ->comment('担当科目や担当範囲に関するメモ');

            $table->string('status', 20)
                ->default(MasterStatus::Active->value)
                ->comment('状態: active / inactive');

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                'status',
                'idx_teachers_status',
            );
        });
    }

    /**
     * 教員情報テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
