<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Validation\Rule;

/**
 * Google Calendar一覧の対象カレンダー・期間・検索条件を検証する。
 */
final class CalendarIndexRequest extends GoogleWorkspaceRequest
{
    /**
     * Calendar一覧へ適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'calendar_id' => ['nullable', 'string', 'max:1024'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'display' => ['nullable', Rule::in(['month', 'agenda'])],
            'anchor' => ['nullable', 'date_format:Y-m-d'],
            'manage' => ['nullable', 'boolean'],
            'refresh' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 表示対象カレンダーIDを返す。
     *
     * @return string 表示対象カレンダーID。未指定時はメインカレンダーを表すprimary
     */
    public function calendarId(): string
    {
        return $this->nullableTrimmed('calendar_id') ?? 'primary';
    }

    /**
     * 予定検索語を返す。
     *
     * @return string|null 前後空白を除いた検索語。未指定時はnull
     */
    public function keyword(): ?string
    {
        return $this->nullableTrimmed('keyword');
    }

    /**
     * 一覧検索で明示された表示開始日を返す。
     *
     * @return string|null Y-m-d形式の開始日。月表示の自動計算を使う場合はnull
     */
    public function dateFrom(): ?string
    {
        return $this->nullableTrimmed('date_from');
    }

    /**
     * 一覧検索で明示された表示終了日を返す。
     *
     * @return string|null Y-m-d形式の終了日。月表示の自動計算を使う場合はnull
     */
    public function dateTo(): ?string
    {
        return $this->nullableTrimmed('date_to');
    }

    /**
     * Calendarの表示形式を返す。
     *
     * @return string 月表示を表すmonth、または予定一覧を表すagenda
     */
    public function displayMode(): string
    {
        return (string) $this->validated('display', 'month');
    }

    /**
     * 月送り・期間計算の基準日を返す。
     *
     * @return string|null Y-m-d形式の基準日。未指定時はnull
     */
    public function anchorDate(): ?string
    {
        return $this->nullableTrimmed('anchor');
    }

    /**
     * カレンダー共有・管理パネルを表示するか返す。
     *
     * @return bool 管理パネルを表示する場合はtrue
     */
    public function showsManagement(): bool
    {
        return $this->boolean('manage');
    }

    /**
     * Google APIのSWRキャッシュを破棄して再取得するか返す。
     *
     * @return bool 利用者が明示的な再読み込みを要求した場合はtrue
     */
    public function shouldRefresh(): bool
    {
        return $this->boolean('refresh');
    }
}
