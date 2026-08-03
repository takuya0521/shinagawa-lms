<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignedCourseIndexRequest extends FormRequest
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
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'grade' => ['nullable', Rule::enum(Grade::class)],
            'class_group_id' => ['nullable', 'integer', Rule::exists('class_groups', 'id')],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
        ];
    }

    /**
     * 対象年度を取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function academicYear(): ?int
    {
        $value = $this->validated('academic_year');

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * 対象学年を取得する。
     *
     * @return ?Grade 処理結果。取得できない場合はnull
     */
    public function grade(): ?Grade
    {
        $value = $this->validated('grade');

        return is_string($value) ? Grade::tryFrom($value) : null;
    }

    /**
     * 対象クラスIDを取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function classGroupId(): ?int
    {
        $value = $this->validated('class_group_id');

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * 対象科目IDを取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function subjectId(): ?int
    {
        $value = $this->validated('subject_id');

        return is_numeric($value) ? (int) $value : null;
    }
}
