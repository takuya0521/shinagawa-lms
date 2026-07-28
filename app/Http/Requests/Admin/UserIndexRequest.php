<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UserIndexRequest extends FormRequest
{
    /**
     * 管理者制御はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ユーザー一覧の検索条件を返す。
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
            'role' => [
                'nullable',
                Rule::enum(UserRole::class),
            ],
            'status' => [
                'nullable',
                Rule::enum(UserStatus::class),
            ],
        ];
    }

    public function keyword(): ?string
    {
        $keyword = $this->validated('keyword');

        return is_string($keyword) && $keyword !== ''
            ? $keyword
            : null;
    }

    public function role(): ?UserRole
    {
        $role = $this->validated('role');

        return is_string($role)
            ? UserRole::tryFrom($role)
            : null;
    }

    public function status(): ?UserStatus
    {
        $status = $this->validated('status');

        return is_string($status)
            ? UserStatus::tryFrom($status)
            : null;
    }

    protected function prepareForValidation(): void
    {
        $keyword = $this->input('keyword');

        $this->merge([
            'keyword' => is_string($keyword)
                ? trim($keyword)
                : $keyword,
        ]);
    }
}
