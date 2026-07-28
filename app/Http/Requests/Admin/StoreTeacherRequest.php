<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreTeacherRequest extends FormRequest
{
    /**
     * 管理者権限の判定はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 教員登録時の入力規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'status' => [
                'required',
                Rule::enum(UserStatus::class),
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'subject_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'teacher_status' => [
                'required',
                Rule::enum(MasterStatus::class),
            ],
        ];
    }

    /**
     * 入力項目の日本語名称を返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '氏名',
            'email' => 'メールアドレス',
            'status' => 'アカウント利用状態',
            'password' => 'パスワード',
            'subject_notes' => '担当科目メモ',
            'teacher_status' => '教員状態',
        ];
    }

    /**
     * 検証前に入力文字列を正規化する。
     */
    protected function prepareForValidation(): void
    {
        $values = [];

        foreach ([
            'name',
            'email',
            'subject_notes',
        ] as $key) {
            $value = $this->input($key);

            if (is_string($value)) {
                $values[$key] = trim($value);
            }
        }

        if (isset($values['email'])) {
            $values['email'] = mb_strtolower($values['email']);
        }

        $this->merge($values);
    }
}
