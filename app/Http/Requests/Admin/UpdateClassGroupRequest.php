<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClassGroupRequest extends FormRequest
{
    /**
     * 管理者権限の判定はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * クラス更新時の入力規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $classGroup = $this->route('classGroup');

        $classCodeRule = Rule::unique(
            'class_groups',
            'class_code',
        );

        if ($classGroup instanceof ClassGroup) {
            $classCodeRule->ignore($classGroup);
        }

        return [
            'class_code' => [
                'required',
                'string',
                'max:30',
                'regex:/\A[A-Z0-9_-]+\z/',
                $classCodeRule,
            ],
            'class_name' => [
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'required',
                Rule::enum(MasterStatus::class),
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
            'class_code' => 'クラスコード',
            'class_name' => 'クラス名',
            'description' => '説明',
            'status' => '状態',
        ];
    }

    /**
     * 独自のエラーメッセージを返す。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'class_code.regex' => 'クラスコードは半角英数字、ハイフン、アンダースコアで入力してください。',
        ];
    }

    /**
     * 検証前に入力文字列を正規化する。
     */
    protected function prepareForValidation(): void
    {
        $classCode = $this->input('class_code');
        $className = $this->input('class_name');
        $description = $this->input('description');

        $this->merge([
            'class_code' => is_string($classCode)
                ? mb_strtoupper(trim($classCode))
                : $classCode,
            'class_name' => is_string($className)
                ? trim($className)
                : $className,
            'description' => is_string($description)
                ? trim($description)
                : $description,
        ]);
    }
}
