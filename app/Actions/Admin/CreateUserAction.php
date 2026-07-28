<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    /**
     * 管理者操作によりユーザーを登録する。
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     role: string,
     *     status: string,
     *     password: string
     * } $input
     */
    public function execute(array $input): User
    {
        return User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => UserRole::from($input['role']),
            'status' => UserStatus::from($input['status']),
        ]);
    }
}
