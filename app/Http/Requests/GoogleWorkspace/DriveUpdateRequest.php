<?php

namespace App\Http\Requests\GoogleWorkspace;

/**
 * Google Driveファイルの名称・説明・スター・配置先更新を検証する。
 */
final class DriveUpdateRequest extends GoogleWorkspaceRequest
{
    /**
     * Driveファイル更新時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'starred' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Serviceへ渡す更新内容を、送信された項目だけに限定して返す。
     *
     * @return array{name?: string, description?: string|null, parent_id?: string|null, starred?: bool} 更新内容
     */
    public function attributes(): array
    {
        $attributes = [];

        if ($this->exists('name')) {
            $name = $this->nullableTrimmed('name');
            if ($name !== null) {
                $attributes['name'] = $name;
            }
        }

        if ($this->exists('description')) {
            $attributes['description'] = $this->nullableTrimmed('description');
        }

        if ($this->exists('parent_id')) {
            $attributes['parent_id'] = $this->nullableTrimmed('parent_id');
        }

        if ($this->exists('starred')) {
            $attributes['starred'] = $this->boolean('starred');
        }

        return $attributes;
    }
}
