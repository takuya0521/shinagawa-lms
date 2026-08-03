<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class ChangeUserStatusAction
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param OperationLogWriter $operationLogWriter 操作ログ記録サービス
     */
    public function __construct(
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * ユーザーの利用状態を変更する。
     *
     * @param User $user 対象ユーザー
     * @param UserStatus $status 設定する状態
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return User 処理結果
     */
    public function execute(
        User $user,
        UserStatus $status,
        User $actor,
        ?string $ipAddress,
    ): User {
        return DB::transaction(function () use (
            $user,
            $status,
            $actor,
            $ipAddress,
        ): User {
            $beforeStatus = $user->status;

            if ($beforeStatus === $status) {
                return $user->refresh();
            }

            $user->update([
                'status' => $status,
            ]);

            $user->refresh();

            $this->operationLogWriter->write(
                actor: $actor,
                action: 'change_user_status',
                target: $user,
                detail: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'before' => [
                        'status' => $beforeStatus->value,
                    ],
                    'after' => [
                        'status' => $user->status->value,
                    ],
                ],
                ipAddress: $ipAddress,
            );

            return $user;
        });
    }
}
