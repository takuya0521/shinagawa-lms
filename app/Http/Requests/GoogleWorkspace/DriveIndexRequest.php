<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Validation\Rule;

/**
 * Google Drive一覧の検索・フォルダ移動条件を検証する。
 */
final class DriveIndexRequest extends GoogleWorkspaceRequest
{
    /**
     * Drive一覧へ適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'page_token' => ['nullable', 'string', 'max:2048'],
            'trash' => ['nullable', 'boolean'],
            'view' => ['nullable', Rule::in(['my-drive', 'shared', 'starred', 'recent', 'trash', 'shared-drive'])],
            'drive_id' => [
                'required_if:view,shared-drive',
                'nullable',
                'string',
                'max:255',
                'regex:/\A[A-Za-z0-9_-]+\z/',
            ],
            'refresh' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 親フォルダIDを返す。
     *
     * @return string|null 親フォルダID
     */
    public function parentId(): ?string
    {
        return $this->nullableTrimmed('parent_id');
    }

    /**
     * 検索語を返す。
     *
     * @return string|null 検索語
     */
    public function keyword(): ?string
    {
        return $this->nullableTrimmed('keyword');
    }

    /**
     * 次ページトークンを返す。
     *
     * @return string|null 次ページトークン
     */
    public function pageToken(): ?string
    {
        return $this->nullableTrimmed('page_token');
    }

    /**
     * ゴミ箱を表示するか返す。
     *
     * @return bool ゴミ箱表示時はtrue
     */
    public function includesTrashed(): bool
    {
        return $this->boolean('trash');
    }

    /**
     * Drive一覧の表示種別を返す。
     *
     * @return string my-drive、shared、starred、recent、trash、shared-driveのいずれか
     */
    public function viewMode(): string
    {
        if ($this->boolean('trash')) {
            return 'trash';
        }

        return (string) $this->validated('view', 'my-drive');
    }

    /**
     * Google APIのSWRキャッシュを破棄して再取得するか返す。
     *
     * @return bool 再取得を要求する場合はtrue
     */
    public function shouldRefresh(): bool
    {
        return $this->boolean('refresh');
    }

    /**
     * 表示対象の共有ドライブIDを返す。
     *
     * @return string|null 共有ドライブID
     */
    public function driveId(): ?string
    {
        return $this->nullableTrimmed('drive_id');
    }
}
