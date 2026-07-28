<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

final class UpdateUserRequest extends FormRequest
{
    /**
     * 管理者権限の判定はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ユーザー更新時の入力規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        $studentId = $user instanceof User
            ? Student::withTrashed()
                ->where('user_id', $user->id)
                ->value('id')
            : null;

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
                Rule::unique('users', 'email')->ignore($user),
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
            'student_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students', 'student_no')
                    ->ignore($studentId),
            ],
            'student_name' => [
                Rule::requiredIf($this->isStudentRole()),
                'nullable',
                'string',
                'max:100',
            ],
            'grade' => [
                Rule::requiredIf($this->isStudentRole()),
                'nullable',
                'string',
                'max:20',
            ],
            'affiliation' => [
                'nullable',
                'string',
                'max:100',
            ],
            'partner_school' => [
                'nullable',
                'string',
                'max:100',
            ],
            'class_group_id' => [
                Rule::requiredIf($this->isStudentRole()),
                'nullable',
                'integer',
                Rule::exists('class_groups', 'id')
                    ->where(
                        fn ($query) => $query->where(
                            'status',
                            MasterStatus::Active->value,
                        ),
                    ),
            ],
            'student_status' => [
                Rule::requiredIf($this->isStudentRole()),
                'nullable',
                Rule::enum(StudentStatus::class),
            ],
        ];
    }

    /**
     * 自分自身のロールと利用状態の変更を拒否する。
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $targetUser = $this->route('user');
                $authenticatedUser = $this->user();

                if (
                    ! $targetUser instanceof User
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
     * 入力項目の日本語名称を返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '氏名',
            'email' => 'メールアドレス',
            'role' => 'ロール',
            'status' => '利用状態',
            'password' => 'パスワード',
            'student_no' => '生徒番号',
            'student_name' => '生徒氏名',
            'grade' => '学年',
            'affiliation' => '所属',
            'partner_school' => '提携校',
            'class_group_id' => 'クラス',
            'student_status' => '在籍状態',
        ];
    }

    /**
     * 検証前に文字列を正規化する。
     */
    protected function prepareForValidation(): void
    {
        $values = [];

        foreach ([
            'name',
            'email',
            'student_no',
            'student_name',
            'grade',
            'affiliation',
            'partner_school',
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

    private function isStudentRole(): bool
    {
        return $this->input('role') === UserRole::Student->value;
    }
}
