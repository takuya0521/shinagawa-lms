<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Meet一覧の会議コード検索条件と更新指定を検証する。
 */
final class MeetIndexRequest extends GoogleWorkspaceRequest
{
    /**
     * Meet一覧へ適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'meeting_code' => [
                'nullable',
                'string',
                'max:128',
                'regex:/\A[A-Za-z0-9_-]+\z/',
            ],
            'refresh' => ['nullable', 'boolean'],
            'swr_refresh' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 会議コードをGoogle Meet API向けの小文字で返す。
     *
     * @return string|null 正規化済み会議コード
     */
    public function meetingCode(): ?string
    {
        $value = $this->nullableTrimmed('meeting_code');

        return $value !== null ? strtolower($value) : null;
    }
}
