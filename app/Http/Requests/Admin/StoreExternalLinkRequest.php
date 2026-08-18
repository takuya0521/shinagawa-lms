<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExternalLinkRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力値へ適用する検証規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'link_type' => ['required', Rule::enum(ExternalLinkType::class)],
            'link_name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url:https', 'max:500'],
            'scope_type' => ['required', Rule::enum(ExternalLinkScopeType::class)],
            'scope_id' => ['nullable', 'integer', 'min:1'],
            'role_scope_id' => ['nullable', 'integer', 'min:1'],
            'class_group_scope_id' => ['nullable', 'integer', 'min:1'],
            'course_scope_id' => ['nullable', 'integer', 'min:1'],
            'student_scope_id' => ['nullable', 'integer', 'min:1'],
            'display_order' => ['required', 'integer', 'between:0,9999'],
            'status' => ['required', Rule::enum(MasterStatus::class)],
        ];
    }

    /**
     * スコープ種別ごとの対象IDを追加検証する。
     */
    /**
     * 基本検証後に実行する追加検証処理を返す。
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $scopeType = ExternalLinkScopeType::tryFrom(
                    (string) $this->input('scope_type'),
                );
                $scopeIdValue = $this->input('scope_id');
                $scopeId = is_numeric($scopeIdValue)
                    ? (int) $scopeIdValue
                    : null;

                if ($scopeType === null) {
                    return;
                }

                $valid = match ($scopeType) {
                    ExternalLinkScopeType::Global => $scopeId === null,
                    ExternalLinkScopeType::Role => $scopeId !== null
                        && UserRole::fromScopeId($scopeId) !== null,
                    ExternalLinkScopeType::ClassGroup => $scopeId !== null
                        && ClassGroup::query()
                            ->whereKey($scopeId)
                            ->exists(),
                    ExternalLinkScopeType::Course => $scopeId !== null
                        && Course::query()
                            ->whereKey($scopeId)
                            ->exists(),
                    ExternalLinkScopeType::Student => $scopeId !== null
                        && Student::query()
                            ->whereKey($scopeId)
                            ->exists(),
                };

                if (! $valid) {
                    $validator->errors()->add(
                        'scope_id',
                        '指定された公開範囲の対象が正しくありません。',
                    );
                }
            },
        ];
    }

    /**
     * 外部リンクへ保存する値を返す。
     *
     * @return array{
     *     link_type: string,
     *     link_name: string,
     *     url: string,
     *     scope_type: string,
     *     scope_id: int|null,
     *     display_order: int,
     *     status: string
     * }
     */
    public function externalLinkAttributes(): array
    {
        $validated = $this->validated();
        $scopeType = ExternalLinkScopeType::from(
            (string) $validated['scope_type'],
        );

        return [
            'link_type' => (string) $validated['link_type'],
            'link_name' => (string) $validated['link_name'],
            'url' => (string) $validated['url'],
            'scope_type' => $scopeType->value,
            'scope_id' => $scopeType === ExternalLinkScopeType::Global
                ? null
                : (int) $validated['scope_id'],
            'display_order' => (int) $validated['display_order'],
            'status' => (string) $validated['status'],
        ];
    }

    /**
     * 検証エラーで使用する項目名を返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'link_type' => 'リンク種別',
            'link_name' => 'リンク名',
            'url' => 'URL',
            'scope_type' => '公開範囲',
            'scope_id' => '公開対象',
            'display_order' => '表示順',
            'status' => '状態',
        ];
    }

    /**
     * 入力検証前にリクエスト値を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $scopeType = ExternalLinkScopeType::tryFrom(
            (string) $this->input('scope_type'),
        );

        $scopeId = match ($scopeType) {
            ExternalLinkScopeType::Role => $this->input('role_scope_id'),
            ExternalLinkScopeType::ClassGroup => $this->input('class_group_scope_id'),
            ExternalLinkScopeType::Course => $this->input('course_scope_id'),
            ExternalLinkScopeType::Student => $this->input('student_scope_id'),
            default => null,
        };

        $this->merge([
            'link_name' => trim((string) $this->input('link_name', '')),
            'url' => trim((string) $this->input('url', '')),
            'scope_id' => $scopeId,
            'display_order' => $this->input('display_order', 0),
        ]);
    }
}
