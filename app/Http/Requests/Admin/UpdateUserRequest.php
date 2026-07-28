<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

final class UpdateUserRequest extends FormRequest
{
    /**
     * 管理者制御はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ユーザー編集時の入力規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        $emailRule = Rule::unique('users', 'email');

        if ($user instanceof User) {
            $emailRule->ignore($user);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                $emailRule,
            ],
            'role' => [
                'required',
                Rule::enum(UserRole::class),
            ],
            'status' => [
                'required',
                Rule::enum(UserStatus::class),
            ],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * 自分自身のロールと利用状態が変更されないことを検証する。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $authenticatedUser = $this->user();
            $targetUser = $this->route('user');

            if (
                ! $authenticatedUser instanceof User
                || ! $targetUser instanceof User
                || ! $authenticatedUser->is($targetUser)
            ) {
                return;
            }

            if (
                $this->input('role') !== $targetUser->role->value
            ) {
                $validator->errors()->add(
                    'role',
                    'ログイン中のユーザー自身のロールは変更できません。',
                );
            }

            if (
                $this->input('status') !== $targetUser->status->value
            ) {
                $validator->errors()->add(
                    'status',
                    'ログイン中のユーザー自身の利用状態は変更できません。',
                );
            }
        });
    }

    /**
     * 検証前に氏名とメールアドレスを正規化する。
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $email = $this->input('email');

        $this->merge([
            'name' => is_string($name)
                ? trim($name)
                : $name,
            'email' => is_string($email)
                ? mb_strtolower(trim($email))
                : $email,
        ]);
    }
}
