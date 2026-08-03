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
     *
     * @return bool 判定結果
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

    /**
     * 検索キーワードを取得する。
     *
     * @return ?string 取得した文字列。未指定時はnull
     */
    public function keyword(): ?string
    {
        $keyword = $this->validated('keyword');

        return is_string($keyword) && $keyword !== ''
            ? $keyword
            : null;
    }

    /**
     * 対象ロールを取得する。
     *
     * @return ?UserRole 処理結果。取得できない場合はnull
     */
    public function role(): ?UserRole
    {
        $role = $this->validated('role');

        return is_string($role)
            ? UserRole::tryFrom($role)
            : null;
    }

    /**
     * 指定された状態を取得する。
     *
     * @return ?UserStatus 処理結果。取得できない場合はnull
     */
    public function status(): ?UserStatus
    {
        $status = $this->validated('status');

        return is_string($status)
            ? UserStatus::tryFrom($status)
            : null;
    }

    /**
     * 入力検証前にリクエスト値を正規化する。
     *
     * @return void 戻り値なし
     */
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
