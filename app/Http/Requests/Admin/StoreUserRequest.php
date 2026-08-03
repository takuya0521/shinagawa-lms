<?php

namespace App\Http\Requests\Admin;

use App\Models\User;

/**
 * ユーザー登録時の入力を検証する。
 */
final class StoreUserRequest extends BaseUserRequest
{
    /**
     * 登録時のパスワード検証規則を返す。
     *
     * @return list<mixed> 必須・確認入力・強度を確認する規則
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'confirmed',
            $this->strongPasswordRule(),
        ];
    }

    /**
     * 登録時は更新対象ユーザーが存在しないためnullを返す。
     *
     * @return User|null 常にnull
     */
    protected function currentUser(): ?User
    {
        return null;
    }
}
