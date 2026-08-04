<?php

namespace App\Actions\Announcement;

use App\Models\Announcement;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class DeleteAnnouncementAction
{
    /**
     * 操作ログ記録サービスを受け取る。
     *
     * @param  OperationLogWriter  $operationLogWriter  操作ログ記録サービス
     */
    public function __construct(
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * お知らせを論理削除し、操作履歴を保存する。
     *
     * @param  Announcement  $announcement  対象お知らせ
     * @param  User  $user  対象ユーザー
     * @param  ?string  $ipAddress  操作元IPアドレス
     * @return void 戻り値なし
     */
    public function execute(
        Announcement $announcement,
        User $user,
        ?string $ipAddress,
    ): void {
        DB::transaction(
            function () use (
                $announcement,
                $user,
                $ipAddress,
            ): void {
                $announcement->delete();

                $this->operationLogWriter->write(
                    actor: $user,
                    action: 'delete_announcement',
                    target: $announcement,
                    detail: ['title' => $announcement->title, 'status' => $announcement->status->value],
                    ipAddress: $ipAddress,
                );
            },
        );
    }
}
