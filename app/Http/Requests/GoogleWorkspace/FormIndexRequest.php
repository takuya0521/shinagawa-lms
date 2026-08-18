<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Forms一覧の検索条件と更新指定を検証する。
 */
final class FormIndexRequest extends GoogleWorkspaceRequest
{
    /**
     * Forms一覧へ適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'refresh' => ['nullable', 'boolean'],
            'swr_refresh' => ['nullable', 'boolean'],
        ];
    }

    /**
     * フォーム名検索語を返す。
     *
     * @return string|null 正規化済み検索語
     */
    public function keyword(): ?string
    {
        return $this->nullableTrimmed('keyword');
    }
}
