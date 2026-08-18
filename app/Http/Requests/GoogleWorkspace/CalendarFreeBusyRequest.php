<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Calendar空き時間確認の対象カレンダーと期間を検証する。
 */
final class CalendarFreeBusyRequest extends GoogleWorkspaceRequest
{
    /**
     * 空き時間確認時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'calendar_ids' => ['required', 'array', 'min:1', 'max:50'],
            'calendar_ids.*' => ['required', 'string', 'max:1024', 'distinct'],
            'time_min' => ['required', 'date'],
            'time_max' => ['required', 'date', 'after:time_min'],
        ];
    }

    /**
     * 確認対象カレンダーID一覧を返す。
     *
     * @return list<string> カレンダーID一覧
     */
    public function calendarIds(): array
    {
        $ids = $this->validated('calendar_ids', []);

        return is_array($ids)
            ? array_values(array_filter($ids, 'is_string'))
            : [];
    }

    /**
     * 確認開始日時を返す。
     *
     * @return string 開始日時
     */
    public function timeMin(): string
    {
        return (string) $this->validated('time_min');
    }

    /**
     * 確認終了日時を返す。
     *
     * @return string 終了日時
     */
    public function timeMax(): string
    {
        return (string) $this->validated('time_max');
    }
}
