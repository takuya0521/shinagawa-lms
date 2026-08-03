<?php

namespace App\Http\Requests\Teacher;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveEvaluationRequest extends FormRequest
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
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'academic_year' => ['required', 'integer', 'between:2000,2100'],
            'term_name' => ['required', Rule::enum(EvaluationTerm::class)],
            'status' => ['required', Rule::enum(EvaluationStatus::class)],
            'evaluations' => ['required', 'array', 'min:1'],
            'evaluations.*.student_id' => [
                'required',
                'integer',
                'distinct',
                'exists:students,id',
            ],
            'evaluations.*.submission_score' => [
                'required',
                'numeric',
                'between:0,100',
                'decimal:0,2',
            ],
            'evaluations.*.attitude_score' => [
                'required',
                'numeric',
                'between:0,100',
                'decimal:0,2',
            ],
        ];
    }

    /**
     * 検証済みの評価入力行を取得する。
     *
     * @return list<array{student_id: int, submission_score: float, attitude_score: float}>
     */
    public function rows(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->validated('evaluations');

        return array_map(
            static fn (array $row): array => [
                'student_id' => (int) $row['student_id'],
                'submission_score' => (float) $row['submission_score'],
                'attitude_score' => (float) $row['attitude_score'],
            ],
            $rows,
        );
    }

    /**
     * 対象学期を取得する。
     *
     * @return EvaluationTerm 処理結果
     */
    public function term(): EvaluationTerm
    {
        return EvaluationTerm::from((string) $this->validated('term_name'));
    }

    /**
     * 指定された状態を取得する。
     *
     * @return EvaluationStatus 処理結果
     */
    public function status(): EvaluationStatus
    {
        return EvaluationStatus::from((string) $this->validated('status'));
    }

    /**
     * 検証エラーで使用する項目名を返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'course_id' => '担当授業',
            'academic_year' => '年度',
            'term_name' => '期間',
            'status' => '状態',
            'evaluations.*.student_id' => '生徒',
            'evaluations.*.submission_score' => '提出物点',
            'evaluations.*.attitude_score' => '授業態度点',
        ];
    }
}
