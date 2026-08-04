<?php

namespace App\Actions\Announcement;

use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class UpdateAnnouncementAction
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
     * お知らせと公開対象を同一トランザクションで更新する。
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{target_type: string, target_value: string|null}>  $targets
     * @param  Announcement  $announcement  対象お知らせ
     * @param  User  $user  対象ユーザー
     * @param  ?string  $ipAddress  操作元IPアドレス
     * @return Announcement 処理結果
     */
    public function execute(
        Announcement $announcement,
        array $attributes,
        array $targets,
        User $user,
        ?string $ipAddress,
    ): Announcement {
        return DB::transaction(
            function () use (
                $announcement,
                $attributes,
                $targets,
                $user,
                $ipAddress,
            ): Announcement {
                $before = [
                    'title' => $announcement->title,
                    'status' => $announcement->status->value,
                    'targets' => $announcement->targets()
                        ->get(['target_type', 'target_value'])
                        ->map(
                            static fn (AnnouncementTarget $target): array => [
                                'target_type' => $target->target_type->value,
                                'target_value' => $target->target_value,
                            ],
                        )
                        ->all(),
                ];

                $announcement->update([
                    ...$attributes,
                    'updated_by' => $user->id,
                ]);

                $announcement->targets()->delete();
                $announcement->targets()->createMany($targets);

                $announcement->refresh();

                $this->operationLogWriter->write(
                    actor: $user,
                    action: ($announcement->status->value === 'published' ? 'publish_announcement' : 'update_announcement'),
                    target: $announcement,
                    detail: ['before' => $before, 'after' => ['title' => $announcement->title, 'status' => $announcement->status->value, 'targets' => $targets]],
                    ipAddress: $ipAddress,
                );

                return $announcement->load('targets');
            },
        );
    }
}
