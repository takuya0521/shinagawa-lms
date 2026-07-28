<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Models\User;

final class ChangeUserStatusAction
{
    /**
     * ユーザーの利用状態を変更する。
     */
    public function execute(
        User $user,
        UserStatus $status,
    ): User {
        $user->update([
            'status' => $status,
        ]);

        return $user->refresh();
    }
}
