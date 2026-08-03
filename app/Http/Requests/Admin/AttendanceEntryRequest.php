<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AttendanceEntryRequest extends FormRequest
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
            'lesson_session_id' => [
                'nullable',
                'integer',
                Rule::exists('lesson_sessions', 'id'),
            ],
            'timetable_slot_id' => [
                'nullable',
                'integer',
                'required_with:lesson_date',
                Rule::exists('timetable_slots', 'id'),
            ],
            'lesson_date' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:timetable_slot_id',
            ],
        ];
    }

    /**
     * 対象授業実施IDを取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function lessonSessionId(): ?int
    {
        $value = $this->validated('lesson_session_id');

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * 対象時間割IDを取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function timetableSlotId(): ?int
    {
        $value = $this->validated('timetable_slot_id');

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * 対象授業日を取得する。
     *
     * @return ?string 取得した文字列。未指定時はnull
     */
    public function lessonDate(): ?string
    {
        $value = $this->validated('lesson_date');

        return is_string($value) ? $value : null;
    }
}
