<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Validation\Rule;

/**
 * Google Classroom一覧の検索・状態絞り込み条件を検証する。
 */
final class ClassroomIndexRequest extends GoogleWorkspaceRequest
{
    /**
     * @return array<string, array<int, mixed>> 検証ルール
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'state' => [
                'nullable',
                'string',
                Rule::in(['ACTIVE', 'ARCHIVED', 'ALL']),
            ],
        ];
    }

    /**
     * @return string|null 前後空白を除いた検索文字列
     */
    public function keyword(): ?string
    {
        $keyword = trim((string) $this->validated('keyword', ''));

        return $keyword !== '' ? $keyword : null;
    }

    /**
     * @return string Classroom APIへ渡す状態条件
     */
    public function state(): string
    {
        $state = $this->validated('state');

        return is_string($state) && $state !== '' ? $state : 'ACTIVE';
    }
}
