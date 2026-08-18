<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ユーザーごとのOAuthトークンを暗号化保存する連携情報テーブルを作成する。
     *
     * 連携先トークンを一意に決定できるよう、1ユーザーにつき1レコードへ制限する。
     */
    public function up(): void
    {
        Schema::create('google_drive_connections', function (Blueprint $table): void {
            $table->id();

            // 同一ユーザーへ複数トークンが混在しないよう、外部キーと一意制約を併用する。
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // 暗号化後の文字列長は一定でないため、トークンはtext型で保持する。
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestampTz('token_expires_at')->nullable();
            $table->string('scope', 500);
            $table->timestampTz('last_connected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * ロールバック時にGoogle Drive連携情報だけを削除し、既存ユーザーは保持する。
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_connections');
    }
};
