<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;

final class CreateTeacherAction
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param CreateUserAction $createUserAction 業務処理
     */
    public function __construct(
        private readonly CreateUserAction $createUserAction,
    ) {}

    /**
     * 教員アカウントと教員情報を登録する。
     *
     * @param  array<string, mixed>  $data
     *
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return Teacher 処理結果
     */
    public function execute(
        array $data,
        User $actor,
        ?string $ipAddress,
    ): Teacher {
        $data['role'] = UserRole::Teacher->value;

        $user = $this->createUserAction->execute(
            data: $data,
            actor: $actor,
            ipAddress: $ipAddress,
        );

        return $user
            ->teacher()
            ->firstOrFail();
    }
}
