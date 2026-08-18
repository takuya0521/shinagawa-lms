<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Validation\Rule;

/**
 * Google Calendar共有ルール追加入力を検証する。
 */
final class CalendarAclRequest extends GoogleWorkspaceRequest
{
    /**
     * Calendar共有ルール追加時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::in(['freeBusyReader', 'reader', 'writer', 'owner'])],
            'send_notification' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 共有先メールアドレスを返す。
     *
     * @return string 共有先メールアドレス
     */
    public function email(): string
    {
        return mb_strtolower($this->trimmed('email'));
    }

    /**
     * 共有ロールを返す。
     *
     * @return string 共有ロール
     */
    public function role(): string
    {
        return (string) $this->validated('role');
    }

    /**
     * 通知メールを送信するか返す。
     *
     * @return bool 通知する場合はtrue
     */
    public function sendsNotification(): bool
    {
        return $this->boolean('send_notification');
    }
}
