<?php

namespace App\Http\Requests\Teacher;

use App\Enums\AttendanceStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class AttendanceIndexRequest extends FormRequest
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
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'attendance_status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'missing_only' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 基本検証後に追加の整合性検証を登録する。
     *
     * @param Validator $validator 検証処理
     * @return void 戻り値なし
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->dateFrom()->diffInDays($this->dateTo()) > 366) {
                $validator->errors()->add('date_to', '集計期間は1年以内で指定してください。');
            }
        });
    }

    /**
     * 検索開始日を取得する。
     *
     * @return CarbonImmutable 処理結果
     */
    public function dateFrom(): CarbonImmutable
    {
        $value = $this->validated('date_from');

        return is_string($value)
            ? CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay()
            : CarbonImmutable::now()->startOfMonth();
    }

    /**
     * 検索終了日を取得する。
     *
     * @return CarbonImmutable 処理結果
     */
    public function dateTo(): CarbonImmutable
    {
        $value = $this->validated('date_to');

        return is_string($value)
            ? CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay()
            : CarbonImmutable::now()->endOfMonth()->startOfDay();
    }

    /**
     * 対象授業IDを取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function courseId(): ?int
    {
        $value = $this->validated('course_id');

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * 出欠状態を取得する。
     *
     * @return ?AttendanceStatus 処理結果。取得できない場合はnull
     */
    public function attendanceStatus(): ?AttendanceStatus
    {
        $value = $this->validated('attendance_status');

        return is_string($value) ? AttendanceStatus::tryFrom($value) : null;
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
