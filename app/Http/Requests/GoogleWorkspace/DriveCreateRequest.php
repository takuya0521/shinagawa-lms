<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Validation\Rule;

/**
 * Google Driveのフォルダ・Google形式ファイル作成入力を検証する。
 */
final class DriveCreateRequest extends GoogleWorkspaceRequest
{
    /**
     * Drive項目作成時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['folder', 'document', 'spreadsheet', 'presentation'])],
            'parent_id' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_-]+\z/'],
        ];
    }

    /**
     * 作成名を返す。
     *
     * @return string 作成名
     */
    public function name(): string
    {
        return $this->trimmed('name');
    }

    /**
     * 作成種別を返す。
     *
     * @return string 作成種別
     */
    public function type(): string
    {
        return (string) $this->validated('type');
    }

    /**
     * 配置先フォルダIDを返す。
     *
     * @return string|null 配置先フォルダID
     */
    public function parentId(): ?string
    {
        return $this->nullableTrimmed('parent_id');
    }
}
