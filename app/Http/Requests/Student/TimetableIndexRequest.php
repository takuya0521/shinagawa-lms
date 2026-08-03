<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class TimetableIndexRequest extends FormRequest
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
}
