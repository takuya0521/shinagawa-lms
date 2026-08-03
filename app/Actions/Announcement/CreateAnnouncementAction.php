<?php

namespace App\Actions\Announcement;

use App\Models\Announcement;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class CreateAnnouncementAction
{
    /**
     * 操作ログ記録サービスを受け取る。
     *
     * @param OperationLogWriter $operationLogWriter 操作ログ記録サービス
     */
    public function __construct(
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * お知らせと公開対象を同一トランザクションで登録する。
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{target_type: string, target_value: string|null}>  $targets
     *
     * @param User $user 対象ユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return Announcement 処理結果
     */
    public function execute(
        array $attributes,
        array $targets,
        User $user,
        ?string $ipAddress,
    ): Announcement {
        return DB::transaction(
            function () use (
                $attributes,
                $targets,
                $user,
                $ipAddress,
            ): Announcement {
                $announcement = Announcement::query()->create([
                    ...$attributes,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                $announcement->targets()->createMany($targets);

                $this->operationLogWriter->write(
                    actor: $user,
                    action: 'create_announcement',
                    target: $announcement,
                    detail: ['title' => $announcement->title, 'status' => $announcement->status->value, 'targets' => $targets],
                    ipAddress: $ipAddress,
                );

                return $announcement->load('targets');
            },
        );
    }
}
