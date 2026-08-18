<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Google Workspaceで付与された全OAuthスコープを欠落なく保存できるよう、
     * scope列を500文字制限のないtext型へ拡張する。
     *
     * Driveのみの連携時は500文字以内だったが、Calendar・Chatを追加すると、
     * Googleが既存の読み取り権限も含めて返すため500文字を超える場合がある。
     */
    public function up(): void
    {
        Schema::table('google_drive_connections', function (Blueprint $table): void {
            // 将来のGoogle API追加でも同じ障害を繰り返さないよう、固定長ではなくtext型を使用する。
            $table->text('scope')->change();
        });
    }

    /**
     * 保存済みスコープが500文字以内の場合に限り、変更前の型へ戻す。
     *
     * 500文字を超える権限情報を切り捨てると再認証判定が壊れるため、
     * データを破壊するロールバックは明示的に拒否する。
     */
    public function down(): void
    {
        $maximumLength = (int) DB::table('google_drive_connections')
            ->selectRaw('COALESCE(MAX(CHAR_LENGTH(scope)), 0) AS maximum_length')
            ->value('maximum_length');

        if ($maximumLength > 500) {
            throw new RuntimeException(
                'scope列に500文字を超える値が保存されているため、安全にロールバックできません。',
            );
        }

        Schema::table('google_drive_connections', function (Blueprint $table): void {
            $table->string('scope', 500)->change();
        });
    }
};
