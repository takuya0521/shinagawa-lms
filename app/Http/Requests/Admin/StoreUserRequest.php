<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends FormRequest
{
    /**
     * 管理者権限の判定はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ユーザー登録時の入力規則を返す。
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
            'role' => [
                'required',
                Rule::enum(UserRole::class),
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
            'student_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students', 'student_no'),
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
