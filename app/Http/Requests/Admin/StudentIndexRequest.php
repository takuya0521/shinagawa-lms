<?php

namespace App\Http\Requests\Admin;

use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StudentIndexRequest extends FormRequest
{
    /**
     * 管理者制御はルートMiddlewareで実施する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 生徒一覧の検索条件を返す。
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
            'grade' => [
                'nullable',
                'string',
                'max:20',
            ],
            'affiliation' => [
                'nullable',
                'string',
                'max:100',
            ],
            'class_group_id' => [
                'nullable',
                'integer',
                Rule::exists('class_groups', 'id'),
            ],
            'status' => [
                'nullable',
                Rule::enum(StudentStatus::class),
            ],
        ];
    }

    public function keyword(): ?string
    {
        return $this->stringValue('keyword');
    }

    public function grade(): ?string
    {
        return $this->stringValue('grade');
    }

    public function affiliation(): ?string
    {
        return $this->stringValue('affiliation');
    }

    public function classGroupId(): ?int
    {
        $classGroupId = $this->validated('class_group_id');

        return is_numeric($classGroupId)
            ? (int) $classGroupId
            : null;
    }

    public function status(): ?StudentStatus
    {
        $status = $this->validated('status');

        return is_string($status)
            ? StudentStatus::tryFrom($status)
            : null;
    }

    /**
     * 検証前に検索文字列を正規化する。
     */
    protected function prepareForValidation(): void
    {
        foreach (['keyword', 'grade', 'affiliation'] as $key) {
            $value = $this->input($key);

            if (is_string($value)) {
                $this->merge([
                    $key => trim($value),
                ]);
            }
        }
    }

    private function stringValue(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
