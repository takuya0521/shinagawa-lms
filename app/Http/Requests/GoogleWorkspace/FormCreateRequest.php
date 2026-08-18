<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Googleフォーム作成時のタイトル・説明・公開指定を検証する。
 */
final class FormCreateRequest extends GoogleWorkspaceRequest
{
    /**
     * Forms作成へ適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    /**
     * フォームタイトルを返す。
     *
     * @return string 正規化済みタイトル
     */
    public function title(): string
    {
        return $this->trimmed('title');
    }

    /**
     * フォーム説明を返す。
     *
     * @return string|null 正規化済み説明
     */
    public function description(): ?string
    {
        return $this->nullableTrimmed('description');
    }

    /**
     * 作成直後に公開するか返す。
     *
     * @return bool 公開する場合はtrue
     */
    public function shouldPublish(): bool
    {
        return $this->boolean('publish');
    }
}
