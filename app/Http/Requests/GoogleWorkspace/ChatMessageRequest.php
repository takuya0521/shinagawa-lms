<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Http\UploadedFile;

/**
 * Google Chatメッセージの投稿・更新入力を検証する。
 */
final class ChatMessageRequest extends GoogleWorkspaceRequest
{
    /**
     * Chatメッセージ保存時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        $maxKilobytes = max(1, (int) config('services.google_chat.upload_max_kb', 204800));

        return [
            'text' => ['required', 'string', 'max:40000'],
            'thread_name' => ['nullable', 'string', 'max:512'],
            'attachment' => ['nullable', 'file', "max:{$maxKilobytes}"],
            'force' => ['nullable', 'boolean'],
        ];
    }

    /**
     * メッセージ本文を返す。
     *
     * @return string メッセージ本文
     */
    public function text(): string
    {
        return $this->trimmed('text');
    }

    /**
     * 返信先スレッドリソース名を返す。
     *
     * @return string|null スレッドリソース名
     */
    public function threadName(): ?string
    {
        return $this->nullableTrimmed('thread_name');
    }

    /**
     * 添付ファイルを返す。
     *
     * @return UploadedFile|null 添付ファイル
     */
    public function attachment(): ?UploadedFile
    {
        $file = $this->file('attachment');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * スレッド返信も強制削除する指定を返す。
     *
     * @return bool 強制削除時はtrue
     */
    public function forceDelete(): bool
    {
        return $this->boolean('force');
    }
}
