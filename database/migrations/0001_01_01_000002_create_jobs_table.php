<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションを実行する。
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->smallInteger('attempts');
            $table->integer('reserved_at')->nullable();
            $table->integer('available_at');
            $table->integer('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });

        // PostgreSQLにはUNSIGNED型がないため、非負制約をCHECK制約で保証する。
        DB::statement(
            'ALTER TABLE jobs ADD CONSTRAINT chk_jobs_attempts_nonnegative CHECK (attempts >= 0)',
        );
        DB::statement(
            'ALTER TABLE jobs ADD CONSTRAINT chk_jobs_reserved_at_nonnegative CHECK (reserved_at IS NULL OR reserved_at >= 0)',
        );
        DB::statement(
            'ALTER TABLE jobs ADD CONSTRAINT chk_jobs_available_at_nonnegative CHECK (available_at >= 0)',
        );
        DB::statement(
            'ALTER TABLE jobs ADD CONSTRAINT chk_jobs_created_at_nonnegative CHECK (created_at >= 0)',
        );
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
