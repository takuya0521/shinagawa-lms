<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 管理者・教員の面談記録登録と更新で共通する入力処理を提供する。
 */
abstract class BaseInterviewRequest extends FormRequest
{
    /**
     * 認証・権限制御は各ロールのルートミドルウェアで行う。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 面談記録の入力ルールを返す。
     *
     * @return array<string, list<mixed>> 入力項目ごとの検証ルール
     */
    final public function rules(): array
    {
        $rules = [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'interview_date' => ['required', 'date_format:Y-m-d'],
            'interview_type' => ['nullable', 'string', 'max:30'],
            'memo' => ['nullable', 'string', 'max:10000'],
            'next_action' => ['nullable', 'string', 'max:10000'],
            'drive_url' => ['nullable', 'url:https', 'max:500'],
            'meet_url' => ['nullable', 'url:https', 'max:500'],
        ];

        if ($this->acceptsTeacherSelection()) {
            $rules['teacher_id'] = [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id')->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    /**
     * 面談記録へ保存する値を返す。
     *
     * @return array<string, int|string|null> 保存対象の面談記録属性
     */
    final public function interviewAttributes(): array
    {
        $validated = $this->validated();
        $attributes = [
            'student_id' => (int) $validated['student_id'],
            'interview_date' => (string) $validated['interview_date'],
            'interview_type' => $this->nullableString(
                $validated,
                'interview_type',
            ),
            'memo' => $this->nullableString($validated, 'memo'),
            'next_action' => $this->nullableString(
                $validated,
                'next_action',
            ),
            'drive_url' => $this->nullableString($validated, 'drive_url'),
            'meet_url' => $this->nullableString($validated, 'meet_url'),
        ];

        if ($this->acceptsTeacherSelection()) {
            $attributes['teacher_id'] = isset($validated['teacher_id'])
                ? (int) $validated['teacher_id']
                : null;
        }

        return $attributes;
    }

    /**
     * 入力項目名を日本語で返す。
     *
     * @return array<string, string> 入力項目名と日本語表示名の対応
     */
    final public function attributes(): array
    {
        $attributes = [
            'student_id' => '生徒',
            'interview_date' => '面談日',
            'interview_type' => '面談種別',
            'memo' => '面談メモ',
            'next_action' => '次回対応',
            'drive_url' => 'Google Drive URL',
            'meet_url' => 'Google Meet URL',
        ];

        if ($this->acceptsTeacherSelection()) {
            $attributes['teacher_id'] = '担当教員';
        }

        return $attributes;
    }

    /**
     * 文字列項目の前後空白を除去し、空文字をnullへ変換する。
     *
     * @return void 戻り値なし
     */
    final protected function prepareForValidation(): void
    {
        $values = [
            'interview_type' => $this->nullableTrimmed('interview_type'),
            'memo' => $this->nullableTrimmed('memo'),
            'next_action' => $this->nullableTrimmed('next_action'),
            'drive_url' => $this->nullableTrimmed('drive_url'),
            'meet_url' => $this->nullableTrimmed('meet_url'),
        ];

        if ($this->acceptsTeacherSelection()) {
            $values['teacher_id'] = $this->filled('teacher_id')
                ? $this->input('teacher_id')
                : null;
        }

        $this->merge($values);
    }

    /**
     * 担当教員を画面入力として受け付けるかを返す。
     *
     * @return bool 管理者画面の場合はtrue
     */
    abstract protected function acceptsTeacherSelection(): bool;

    /**
     * 検証済み配列から空でない文字列を取得する。
     *
     * @param array<string, mixed> $validated 検証済み入力
     * @param string $key 取得する項目名
     * @return string|null 取得した文字列
     */
    private function nullableString(
        array $validated,
        string $key,
    ): ?string {
        $value = $validated[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * 入力値の前後空白を除去し、空文字の場合はnullを返す。
     *
     * @param string $key 取得する入力項目名
     * @return string|null 整形後の文字列
     */
    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
