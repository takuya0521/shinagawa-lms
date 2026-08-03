<?php

namespace App\Http\Requests\Admin;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

/**
 * ユーザー登録・更新で共通する入力規則と入力整形を提供する。
 */
abstract class BaseUserRequest extends FormRequest
{
    /**
     * 認証・権限制御は管理者ルートのミドルウェアで行う。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ユーザー登録・更新で共通する入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力項目ごとの検証規則
     */
    final public function rules(): array
    {
        $user = $this->currentUser();
        $studentId = $this->currentStudentId($user);

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
                $this->emailUniqueRule($user),
            ],
            'role' => [
                'required',
                Rule::enum(UserRole::class),
            ],
            'status' => [
                'required',
                Rule::enum(UserStatus::class),
            ],
            'password' => $this->passwordRules(),
            'student_no' => [
                'nullable',
                'string',
                'max:50',
                $this->studentNumberUniqueRule($studentId),
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
                Rule::enum(Grade::class),
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
                Rule::exists('class_groups', 'id')->where(
                    static fn ($query) => $query->where(
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
     * 入力項目名を日本語で返す。
     *
     * @return array<string, string> 入力項目名と日本語表示名の対応
     */
    final public function attributes(): array
    {
        return [
            'name' => 'アカウント氏名',
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
     * 文字列の前後空白、メールアドレス、生徒管理画面のロールを正規化する。
     *
     * @return void 戻り値なし
     */
    final protected function prepareForValidation(): void
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

        if ($this->isStudentManagementRoute()) {
            $values['role'] = UserRole::Student->value;
        }

        $this->merge($values);
    }

    /**
     * 登録・更新に応じたパスワード検証規則を返す。
     *
     * @return list<mixed> パスワード項目の検証規則
     */
    abstract protected function passwordRules(): array;

    /**
     * 更新対象のユーザーを返す。
     *
     * 登録時はnull、更新時はルートモデルに対応するユーザーを返す。
     *
     * @return User|null 更新対象のユーザー
     */
    abstract protected function currentUser(): ?User;

    /**
     * 共通の強固なパスワード規則を返す。
     *
     * @return Password パスワード検証規則
     */
    final protected function strongPasswordRule(): Password
    {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * 入力ロールが生徒かを判定する。
     *
     * @return bool 生徒ロールの場合はtrue
     */
    private function isStudentRole(): bool
    {
        return $this->input('role') === UserRole::Student->value;
    }

    /**
     * 生徒管理の登録・更新ルートかを判定する。
     *
     * @return bool 生徒管理ルートの場合はtrue
     */
    private function isStudentManagementRoute(): bool
    {
        return $this->routeIs(
            'admin.students.store',
            'admin.students.update',
        );
    }

    /**
     * 更新対象ユーザーに紐付く生徒IDを返す。
     *
     * @param User|null $user 更新対象のユーザー
     * @return int|null 生徒ID。生徒情報がない場合はnull
     */
    private function currentStudentId(?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        $studentId = Student::withTrashed()
            ->where('user_id', $user->id)
            ->value('id');

        return is_numeric($studentId) ? (int) $studentId : null;
    }

    /**
     * メールアドレスの一意性確認規則を返す。
     *
     * @param User|null $user 更新対象のユーザー
     * @return Unique 一意性確認規則
     */
    private function emailUniqueRule(?User $user): Unique
    {
        $rule = Rule::unique('users', 'email');

        return $user === null ? $rule : $rule->ignore($user);
    }

    /**
     * 生徒番号の一意性確認規則を返す。
     *
     * @param int|null $studentId 更新対象の生徒ID
     * @return Unique 一意性確認規則
     */
    private function studentNumberUniqueRule(?int $studentId): Unique
    {
        $rule = Rule::unique('students', 'student_no');

        return $studentId === null ? $rule : $rule->ignore($studentId);
    }
}
