<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;

final class UpdateTeacherAction
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param UpdateUserAction $updateUserAction 業務処理
     */
    public function __construct(
        private readonly UpdateUserAction $updateUserAction,
    ) {}

    /**
     * 教員アカウントと教員情報を更新する。
     *
     * @param  array<string, mixed>  $data
     *
     * @param Teacher $teacher 対象教員
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return Teacher 処理結果
     */
    public function execute(
        Teacher $teacher,
        array $data,
        User $actor,
        ?string $ipAddress,
    ): Teacher {
        $data['role'] = UserRole::Teacher->value;

        $this->updateUserAction->execute(
            user: $teacher->user,
            data: $data,
            actor: $actor,
            ipAddress: $ipAddress,
        );

        return $teacher
            ->refresh()
            ->load('user');
    }
}
