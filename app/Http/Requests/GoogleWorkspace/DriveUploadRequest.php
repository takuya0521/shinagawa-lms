<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Http\UploadedFile;

/**
 * Google Driveへのファイルアップロード入力を検証する。
 */
final class DriveUploadRequest extends GoogleWorkspaceRequest
{
    /**
     * Driveアップロード時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        $maxKilobytes = max(1, (int) config('services.google_drive.upload_max_kb', 102400));

        return [
            'file' => ['required', 'file', "max:{$maxKilobytes}"],
            'parent_id' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_-]+\z/'],
        ];
    }

    /**
     * 検証済みアップロードファイルを返す。
     *
     * @return UploadedFile アップロードファイル
     */
    public function uploadedFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
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
