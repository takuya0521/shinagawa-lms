<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\Validator;

/**
 * ユーザー更新時の入力を検証する。
 */
final class UpdateUserRequest extends BaseUserRequest
{
    /**
     * ログイン中のユーザー自身によるロール変更と利用停止を拒否する。
     *
     * @return list<callable> 共通検証後に実行する追加検証
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $targetUser = $this->currentUser();
                $authenticatedUser = $this->user();

                if (
                    $targetUser === null
                    || ! $authenticatedUser instanceof User
                    || ! $targetUser->is($authenticatedUser)
                ) {
                    return;
                }

                if ($this->input('role') !== $targetUser->role->value) {
                    $validator->errors()->add(
                        'role',
                        'ログイン中のユーザー自身のロールは変更できません。',
                    );
                }

                if ($this->input('status') !== $targetUser->status->value) {
                    $validator->errors()->add(
                        'status',
                        'ログイン中のユーザー自身を利用停止にできません。',
                    );
                }
            },
        ];
    }

    /**
     * 更新時のパスワード検証規則を返す。
     *
     * @return list<mixed> 未入力を許可し、入力時だけ確認と強度を確認する規則
     */
    protected function passwordRules(): array
    {
        return [
            'nullable',
            'confirmed',
            $this->strongPasswordRule(),
        ];
    }

    /**
     * ユーザー管理または生徒管理のルートから更新対象ユーザーを取得する。
     *
     * @return User|null 更新対象のユーザー
     */
    protected function currentUser(): ?User
    {
        $user = $this->route('user');

        if ($user instanceof User) {
            return $user;
        }

        $student = $this->route('student');

        return $student instanceof Student ? $student->user : null;
    }
}
