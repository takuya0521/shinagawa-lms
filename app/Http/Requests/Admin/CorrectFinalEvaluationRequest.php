<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CorrectFinalEvaluationRequest extends FormRequest
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
            'submission_score' => [
                'required',
                'numeric',
                'between:0,100',
                'decimal:0,2',
            ],
            'attendance_score' => [
                'required',
                'numeric',
                'between:0,100',
                'decimal:0,2',
            ],
            'attitude_score' => [
                'required',
                'numeric',
                'between:0,100',
                'decimal:0,2',
            ],
            'correction_reason' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * 評価修正に使用する検証済み属性を取得する。
     *
     * @return array{submission_score: float, attendance_score: float, attitude_score: float, correction_reason: string}
     */
    public function correctionAttributes(): array
    {
        $validated = $this->validated();

        return [
            'submission_score' => (float) $validated['submission_score'],
            'attendance_score' => (float) $validated['attendance_score'],
            'attitude_score' => (float) $validated['attitude_score'],
            'correction_reason' => trim((string) $validated['correction_reason']),
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
            'submission_score' => '提出物点',
            'attendance_score' => '出欠点',
            'attitude_score' => '授業態度点',
            'correction_reason' => '修正理由',
        ];
    }

    /**
     * 入力検証前にリクエスト値を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'correction_reason' => trim(
                (string) $this->input('correction_reason', ''),
            ),
        ]);
    }
}
