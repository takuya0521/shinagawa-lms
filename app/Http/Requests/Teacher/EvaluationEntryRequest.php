<?php

namespace App\Http\Requests\Teacher;

use App\Enums\EvaluationTerm;
use App\Support\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EvaluationEntryRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'term_name' => ['nullable', Rule::enum(EvaluationTerm::class)],
        ];
    }

    /**
     * 対象授業IDを取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function courseId(): ?int
    {
        $courseId = $this->integer('course_id');

        return $courseId > 0 ? $courseId : null;
    }

    /**
     * 対象年度を取得する。
     *
     * @return int 取得した整数
     */
    public function academicYear(): int
    {
        $defaultYear = AcademicYear::forDate();

        return $this->integer('academic_year', $defaultYear);
    }

    /**
     * 対象学期を取得する。
     *
     * @return EvaluationTerm 処理結果
     */
    public function term(): EvaluationTerm
    {
        return EvaluationTerm::tryFrom((string) $this->input('term_name'))
            ?? EvaluationTerm::Annual;
    }
}
