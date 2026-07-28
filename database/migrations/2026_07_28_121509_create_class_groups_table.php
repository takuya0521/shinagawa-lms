<?php

use App\Enums\MasterStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 午前・午後などのクラスグループを作成する。
     */
    public function up(): void
    {
        Schema::create('class_groups', function (Blueprint $table): void {
            $table->id();

            $table->string('class_code', 30)
                ->unique()
                ->comment('クラスコード: AM / PMなど');

            $table->string('class_name', 100)
                ->comment('クラス名');

            $table->string('description', 255)
                ->nullable()
                ->comment('説明');

            $table->string('status', 20)
                ->default(MasterStatus::Active->value)
                ->comment('状態: active / inactive');

            $table->timestamps();
        });
    }

    /**
     * クラスグループを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('class_groups');
    }
};
