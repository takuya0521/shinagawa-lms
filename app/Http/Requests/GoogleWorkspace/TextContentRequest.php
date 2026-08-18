<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Driveコメント・返信などの短文入力を検証する。
 */
final class TextContentRequest extends GoogleWorkspaceRequest
{
    /**
     * 本文入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return ['content' => ['required', 'string', 'max:5000']];
    }

    /**
     * 前後空白除去済み本文を返す。
     *
     * @return string 本文
     */
    public function content(): string
    {
        return $this->trimmed('content');
    }
}
