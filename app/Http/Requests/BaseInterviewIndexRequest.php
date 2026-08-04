<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 管理者・教員の面談履歴検索で共通利用する入力処理を定義する。
 */
abstract class BaseInterviewIndexRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 面談履歴検索で共通利用する検証規則を返す。
     *
     * @return array<string, list<mixed>> 共通の検証規則
     */
    protected function commonRules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
            'keyword' => ['nullable', 'string', 'max:255'],
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists('students', 'id')
                    ->whereNull('deleted_at'),
            ],
            'interview_type' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * 検索開始日を取得する。
     *
     * @return CarbonImmutable 検索開始日
     */
    public function dateFrom(): CarbonImmutable
    {
        $value = $this->validated('date_from');

        return is_string($value)
            ? CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay()
            : CarbonImmutable::now()->subMonthsNoOverflow(6)->startOfDay();
    }

    /**
     * 検索終了日を取得する。
     *
     * @return CarbonImmutable 検索終了日
     */
    public function dateTo(): CarbonImmutable
    {
        $value = $this->validated('date_to');

        return is_string($value)
            ? CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay()
            : CarbonImmutable::now()->startOfDay();
    }

    /**
     * 検索キーワードを取得する。
     *
     * @return string|null 検索キーワード。未指定時はnull
     */
    public function keyword(): ?string
    {
        return $this->validatedNullableString('keyword');
    }

    /**
     * 指定項目のIDを取得する。
     *
     * @param  string  $key  取得対象の項目名
     * @return int|null ID。未指定時はnull
     */
    public function nullableId(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * 面談種別を取得する。
     *
     * @return string|null 面談種別。未指定時はnull
     */
    public function interviewType(): ?string
    {
        return $this->validatedNullableString('interview_type');
    }

    /**
     * 入力検証前に文字列の前後空白と空文字を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'keyword' => $this->inputNullableTrimmedString('keyword'),
            'interview_type' => $this->inputNullableTrimmedString('interview_type'),
        ]);
    }

    /**
     * 検証済み項目を空文字を除外した文字列として取得する。
     *
     * @param  string  $key  取得対象の項目名
     * @return string|null 文字列。未指定時はnull
     */
    private function validatedNullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * 入力項目を前後空白を除去した文字列として取得する。
     *
     * @param  string  $key  取得対象の項目名
     * @return string|null 文字列。空文字の場合はnull
     */
    private function inputNullableTrimmedString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
