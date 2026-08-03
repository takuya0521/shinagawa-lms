<?php

namespace App\Services;

use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class OperationLogWriter
{
    /**
     * Eloquentモデルを対象とした重要な業務操作を操作ログへ記録する。
     *
     * @param User $actor 操作を実行した利用者
     * @param string $action 操作内容を識別するコード
     * @param Model $target 操作対象のEloquentモデル
     * @param array<string, mixed> $detail 機密情報を除いた操作詳細
     * @param string|null $ipAddress 操作元のIPアドレス
     * @return OperationLog 登録した操作ログ
     */
    public function write(
        User $actor,
        string $action,
        Model $target,
        array $detail = [],
        ?string $ipAddress = null,
    ): OperationLog {
        return $this->writeForTarget(
            actor: $actor,
            action: $action,
            targetTable: $target->getTable(),
            targetId: (int) $target->getKey(),
            detail: $detail,
            ipAddress: $ipAddress,
        );
    }

    /**
     * モデルを直接指定できない操作をテーブル名と対象IDで記録する。
     *
     * @param User $actor 操作を実行した利用者
     * @param string $action 操作内容を識別するコード
     * @param string $targetTable 操作対象のテーブル名
     * @param int|null $targetId 操作対象ID。対象を1件に限定できない場合はnull
     * @param array<string, mixed> $detail 機密情報を除いた操作詳細
     * @param string|null $ipAddress 操作元のIPアドレス
     * @return OperationLog 登録した操作ログ
     */
    public function writeForTarget(
        User $actor,
        string $action,
        string $targetTable,
        ?int $targetId,
        array $detail = [],
        ?string $ipAddress = null,
    ): OperationLog {
        return OperationLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'detail' => [
                ...$detail,
                'ip_address' => $ipAddress,
            ],
        ]);
    }
}
