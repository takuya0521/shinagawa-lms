<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Googleフォームの公開状態変更を検証する。
 */
final class FormPublishRequest extends GoogleWorkspaceRequest
{
    /**
     * 公開状態変更へ適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'published' => ['required', 'boolean'],
        ];
    }

    /**
     * 公開するか返す。
     *
     * @return bool 公開する場合はtrue
     */
    public function published(): bool
    {
        return $this->boolean('published');
    }
}
