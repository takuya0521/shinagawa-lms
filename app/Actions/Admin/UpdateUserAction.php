<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class UpdateUserAction
{
    /**
     * 管理者操作によりユーザー情報を更新する。
     *
     * パスワードが未入力の場合は、現在のパスワードを維持する。
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     role: string,
     *     status: string,
     *     password?: string|null
     * } $input
     */
    public function execute(User $user, array $input): User
    {
        $attributes = [
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => UserRole::from($input['role']),
            'status' => UserStatus::from($input['status']),
        ];

        $password = $input['password'] ?? null;

        if (is_string($password) && $password !== '') {
            $attributes['password'] = Hash::make($password);
        }

        $user->update($attributes);

        return $user->refresh();
    }
}
