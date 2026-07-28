<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TeacherIndexRequest extends FormRequest
{
    /**
     * 管理者権限の判定はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 教員一覧の検索条件を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'keyword' => [
                'nullable',
                'string',
                'max:255',
            ],
            'teacher_status' => [
                'nullable',
                Rule::enum(MasterStatus::class),
            ],
            'account_status' => [
                'nullable',
                Rule::enum(UserStatus::class),
            ],
        ];
    }

    /**
     * 検索キーワードを返す。
     */
    public function keyword(): ?string
    {
        $keyword = $this->validated('keyword');

        return is_string($keyword) && $keyword !== ''
            ? $keyword
            : null;
    }

    /**
     * 教員情報の状態を返す。
     */
    public function teacherStatus(): ?MasterStatus
    {
        $status = $this->validated('teacher_status');

        return is_string($status)
            ? MasterStatus::tryFrom($status)
            : null;
    }

    /**
     * アカウントの利用状態を返す。
     */
    public function accountStatus(): ?UserStatus
    {
        $status = $this->validated('account_status');

        return is_string($status)
            ? UserStatus::tryFrom($status)
            : null;
    }

    /**
     * 検証前に検索文字列を正規化する。
     */
    protected function prepareForValidation(): void
    {
        $keyword = $this->input('keyword');

        if (! is_string($keyword)) {
            return;
        }

        $this->merge([
            'keyword' => trim($keyword),
        ]);
    }
}
