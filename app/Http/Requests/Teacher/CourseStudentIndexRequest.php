<?php

namespace App\Http\Requests\Teacher;

use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CourseStudentIndexRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
        ];
    }

    /**
     * 検索キーワードを取得する。
     *
     * @return ?string 取得した文字列。未指定時はnull
     */
    public function keyword(): ?string
    {
        $value = $this->validated('keyword');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * 指定された状態を取得する。
     *
     * @return ?StudentStatus 処理結果。取得できない場合はnull
     */
    public function status(): ?StudentStatus
    {
        $value = $this->validated('status');

        return is_string($value) ? StudentStatus::tryFrom($value) : null;
    }

    /**
     * 入力検証前にリクエスト値を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $keyword = $this->input('keyword');

        if (is_string($keyword)) {
            $this->merge(['keyword' => trim($keyword)]);
        }
    }
}
