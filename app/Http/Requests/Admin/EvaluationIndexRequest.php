<?php

namespace App\Http\Requests\Admin;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Support\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EvaluationIndexRequest extends FormRequest
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
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'term_name' => ['nullable', Rule::enum(EvaluationTerm::class)],
            'student_id' => ['nullable', 'integer'],
            'course_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(EvaluationStatus::class)],
            'missing_only' => ['nullable', 'boolean'],
        ];
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

    /**
     * 指定項目のIDを取得する。
     *
     * @param string $key 取得対象のキー
     * @return ?int 取得した整数。未指定時はnull
     */
    public function nullableId(string $key): ?int
    {
        $value = $this->integer($key);

        return $value > 0 ? $value : null;
    }

    /**
     * 評価状態を取得する。
     *
     * @return ?EvaluationStatus 処理結果。取得できない場合はnull
     */
    public function evaluationStatus(): ?EvaluationStatus
    {
        return EvaluationStatus::tryFrom((string) $this->input('status'));
    }

    /**
     * 未登録データだけを対象とするか判定する。
     *
     * @return bool 判定結果
     */
    public function missingOnly(): bool
    {
        return $this->boolean('missing_only');
    }
}
