<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Calendarの名称・説明・タイムゾーン入力を検証する。
 */
final class CalendarRequest extends GoogleWorkspaceRequest
{
    /**
     * カレンダー保存時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'time_zone' => ['required', 'timezone:all'],
        ];
    }

    /**
     * カレンダー名を返す。
     *
     * @return string カレンダー名
     */
    public function summary(): string
    {
        return $this->trimmed('summary');
    }

    /**
     * 説明を返す。
     *
     * @return string|null 説明
     */
    public function description(): ?string
    {
        return $this->nullableTrimmed('description');
    }

    /**
     * タイムゾーンを返す。
     *
     * @return string タイムゾーン
     */
    public function timeZone(): string
    {
        return (string) $this->validated('time_zone');
    }
}
