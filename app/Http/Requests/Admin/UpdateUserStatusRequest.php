<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateUserStatusRequest extends FormRequest
{
    /**
     * 管理者制御はルートMiddlewareで実施する。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 利用状態変更時の入力規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(UserStatus::class),
            ],
        ];
    }

    /**
     * ログイン中の管理者自身が利用停止されないことを検証する。
     *
     * @param Validator $validator 検証処理
     * @return void 戻り値なし
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

            if ($this->input('status') !== $targetUser->status->value) {
                $validator->errors()->add(
                    'status',
                    'ログイン中のユーザー自身の利用状態は変更できません。',
                );
            }
        });
    }

    /**
     * 検証済みの利用状態をEnumとして返す。
     *
     * @return UserStatus 処理結果
     */
    public function status(): UserStatus
    {
        return UserStatus::from(
            (string) $this->validated('status'),
        );
    }
}
