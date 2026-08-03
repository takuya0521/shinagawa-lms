<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力値へ適用する検証規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
                'confirmed',
            ],
        ];
    }

    /**
     * 検証エラーで使用する項目名を返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => '現在のパスワード',
            'password' => '新しいパスワード',
            'password_confirmation' => '確認用パスワード',
        ];
    }
}
