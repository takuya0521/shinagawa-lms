<?php

namespace App\Http\Requests\Auth;

use App\Support\Auth\LoginCredentialRules;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

/**
 * LMSのログイン画面で使用する入力検証を定義する。
 */
final class LoginRequest extends FortifyLoginRequest
{
    /**
     * ログイン画面の入力検証規則を返す。
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return LoginCredentialRules::rules();
    }
}
