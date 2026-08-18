<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Chatスペースへ追加するメンバー入力を検証する。
 */
final class ChatMemberRequest extends GoogleWorkspaceRequest
{
    /**
     * Chatメンバー追加時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return ['email' => ['required', 'email:rfc', 'max:255']];
    }

    /**
     * 追加対象メールアドレスを返す。
     *
     * @return string メールアドレス
     */
    public function email(): string
    {
        return mb_strtolower($this->trimmed('email'));
    }
}
