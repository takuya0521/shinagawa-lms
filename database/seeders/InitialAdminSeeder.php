<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class InitialAdminSeeder extends Seeder
{
    /**
     * 開発環境で使用する初期管理者を登録する。
     */
    public function run(): void
    {
        $name = config('lms.initial_admin.name');
        $email = config('lms.initial_admin.email');
        $password = config('lms.initial_admin.password');

        if (
            ! is_string($name)
            || ! is_string($email)
            || ! is_string($password)
            || $name === ''
            || $email === ''
            || $password === ''
        ) {
            throw new RuntimeException(
                '初期管理者の環境変数が設定されていません。',
            );
        }

        User::query()->updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
    }
}