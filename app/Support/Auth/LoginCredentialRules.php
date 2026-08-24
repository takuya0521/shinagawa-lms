<?php

namespace App\Support\Auth;

/**
 * ログイン画面で使用する入力検証規則を一元管理する。
 */
final class LoginCredentialRules
{
    /**
     * ログイン画面の入力検証規則を返す。
     *
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
            ],
            'remember' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
