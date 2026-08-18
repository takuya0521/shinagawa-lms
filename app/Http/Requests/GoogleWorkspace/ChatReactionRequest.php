<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Chatメッセージへ追加するUnicodeリアクションを検証する。
 */
final class ChatReactionRequest extends GoogleWorkspaceRequest
{
    /**
     * Chatリアクション追加時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return ['emoji' => ['required', 'string', 'max:32']];
    }

    /**
     * Unicode絵文字を返す。
     *
     * @return string Unicode絵文字
     */
    public function emoji(): string
    {
        return $this->trimmed('emoji');
    }
}
