<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * 入力内容を検証し、忘れたパスワードを再設定する。
     *
     * @param User $user パスワードを再設定するユーザー
     * @param array<string, string> $input 新しいパスワードと確認入力
     * @return void
     * @throws ValidationException 入力内容がパスワード条件を満たさない場合
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
