<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ユーザーへロールと利用状態を追加する。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // 未指定時に管理者権限を付与しないため、最小権限の生徒を初期値とする。
            $table->string('role', 20)
                ->default(UserRole::Student->value)
                ->after('password')
                ->comment('ユーザーロール: admin / teacher / student');

            $table->string('status', 20)
                ->default(UserStatus::Active->value)
                ->after('role')
                ->comment('利用状態: active / suspended');

            $table->index('role');
            $table->index('status');
        });
    }

    /**
     * ユーザーからロールと利用状態を削除する。
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn(['role', 'status']);
        });
    }
};
