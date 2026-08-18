<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Driveファイル複製入力を検証する。
 */
final class DriveCopyRequest extends GoogleWorkspaceRequest
{
    /**
     * Driveファイル複製時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_-]+\z/'],
        ];
    }

    /**
     * 複製後の名称を返す。
     *
     * @return string|null 複製後名称
     */
    public function name(): ?string
    {
        return $this->nullableTrimmed('name');
    }

    /**
     * 複製先フォルダIDを返す。
     *
     * @return string|null 複製先フォルダID
     */
    public function parentId(): ?string
    {
        return $this->nullableTrimmed('parent_id');
    }
}
