<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Validation\Rule;

/**
 * Google Calendar予定の作成・更新入力を検証する。
 */
final class CalendarEventRequest extends GoogleWorkspaceRequest
{
    /**
     * Calendar予定保存時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'calendar_id' => ['required', 'string', 'max:1024'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:1000'],
            'all_day' => ['nullable', 'boolean'],
            'start_date' => ['required_if:all_day,1', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:all_day,1', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_at' => ['required_unless:all_day,1', 'nullable', 'date'],
            'end_at' => ['required_unless:all_day,1', 'nullable', 'date', 'after:start_at'],
            'time_zone' => ['required', 'timezone:all'],
            'attendees' => ['nullable', 'string', 'max:10000'],
            'recurrence' => ['required', Rule::in(['none', 'daily', 'weekly', 'monthly', 'yearly'])],
            'recurrence_until' => ['nullable', 'date_format:Y-m-d'],
            'reminder_minutes' => ['nullable', 'array', 'max:5'],
            'reminder_minutes.*' => ['integer', 'between:0,40320'],
            'create_meet' => ['nullable', 'boolean'],
            'send_updates' => ['required', Rule::in(['all', 'externalOnly', 'none'])],
        ];
    }

    /**
     * Serviceへ渡す予定属性を返す。
     *
     * @return array<string, mixed> 予定属性
     */
    public function attributes(): array
    {
        $attendees = preg_split('/[\s,;]+/u', (string) $this->input('attendees', ''));
        $attendees = is_array($attendees)
            ? array_values(array_unique(array_filter(array_map('trim', $attendees))))
            : [];
        $reminders = $this->validated('reminder_minutes', []);

        return [
            'summary' => $this->trimmed('title'),
            'description' => $this->nullableTrimmed('description'),
            'location' => $this->nullableTrimmed('location'),
            'all_day' => $this->boolean('all_day'),
            'start_date' => $this->nullableTrimmed('start_date'),
            'end_date' => $this->nullableTrimmed('end_date'),
            'start_at' => $this->nullableTrimmed('start_at'),
            'end_at' => $this->nullableTrimmed('end_at'),
            'time_zone' => (string) $this->validated('time_zone'),
            'attendees' => $attendees,
            'recurrence' => (string) $this->validated('recurrence'),
            'recurrence_until' => $this->nullableTrimmed('recurrence_until'),
            'reminder_minutes' => is_array($reminders)
                ? array_values(array_map('intval', $reminders))
                : [],
            'create_meet' => $this->boolean('create_meet'),
            'send_updates' => (string) $this->validated('send_updates'),
        ];
    }

    /**
     * 操作対象カレンダーIDを返す。
     *
     * @return string カレンダーID
     */
    public function calendarId(): string
    {
        return (string) $this->validated('calendar_id');
    }
}
