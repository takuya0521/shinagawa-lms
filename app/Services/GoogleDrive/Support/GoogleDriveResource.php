<?php

namespace App\Services\GoogleDrive\Support;

use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use Illuminate\Support\Str;

/**
 * DriveリソースIDの検証、URL生成、エクスポート形式の解決を担当する。
 */
final class GoogleDriveResource
{
    /**
     * URL生成・ID検証・エクスポート形式解決を各Serviceへ重複させないため、Drive設定Serviceを注入する。
     *
     * @param  GoogleDriveConfiguration  $configuration  Drive APIのURI・スコープ・取得上限を提供する設定サービス
     */
    public function __construct(
        private readonly GoogleDriveConfiguration $configuration,
    ) {}

    /**
     * 指定ファイルを操作するAPI URLを組み立てる。
     *
     * @param  string  $fileId  Google DriveのファイルID
     * @return string 指定ファイルを操作するDrive API URL
     */
    public function fileUrl(string $fileId): string
    {
        return $this->configuration->filesUri().'/'.$this->fileId($fileId);
    }

    /**
     * 外部入力されたファイルIDをURL利用向けに整形する。
     *
     * @param  string  $fileId  Google DriveのファイルID
     * @return string 形式検証済みのファイルID
     *
     * @throws GoogleDriveResponseException ファイルIDの形式が不正な場合
     */
    public function fileId(string $fileId): string
    {
        if (preg_match('/\A[A-Za-z0-9_-]+\z/u', $fileId) !== 1) {
            throw new GoogleDriveResponseException('Google DriveのファイルIDが不正です。');
        }

        return $fileId;
    }

    /**
     * 子リソースIDをURL利用向けに整形する。
     *
     * @param  string  $resourceId  コメントや権限など子リソースのID
     * @return string 形式検証済みの子リソースID
     *
     * @throws GoogleDriveResponseException 子リソースIDの形式が不正な場合
     */
    public function childResourceId(string $resourceId): string
    {
        if (preg_match('/\A[A-Za-z0-9_.-]+\z/u', $resourceId) !== 1) {
            throw new GoogleDriveResponseException('Google DriveのリソースIDが不正です。');
        }

        return $resourceId;
    }

    /**
     * 保存先フォルダIDをDrive API向けに正規化する。
     *
     * @param  ?string  $parentId  保存先フォルダID
     * @return string Drive APIへ送信する保存先フォルダID
     */
    public function parentId(?string $parentId): string
    {
        return $parentId === null || $parentId === ''
            ? 'root'
            : $this->fileId($parentId);
    }

    /**
     * Google形式ファイルのエクスポート形式を決定する。
     *
     * @param  string  $mimeType  MIMEタイプ
     * @return array{0: string, 1: string} エクスポート用MIMEタイプとファイル拡張子
     *
     * @throws GoogleDriveResponseException 対応していないGoogle形式ファイルの場合
     */
    public function exportFormat(string $mimeType): array
    {
        return match ($mimeType) {
            'application/vnd.google-apps.document' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'docx',
            ],
            'application/vnd.google-apps.spreadsheet' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xlsx',
            ],
            'application/vnd.google-apps.presentation' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'pptx',
            ],
            'application/vnd.google-apps.drawing' => ['application/pdf', 'pdf'],
            default => throw new GoogleDriveResponseException(
                'このGoogle WorkspaceファイルはLMSからエクスポートできません。',
            ),
        };
    }

    /**
     * ダウンロード時に使用する安全なファイル名を返す。
     *
     * @param  string  $name  表示名
     * @param  string  $extension  付与する拡張子
     * @return string 拡張子を補完したダウンロード用ファイル名
     */
    public function filenameWithExtension(string $name, string $extension): string
    {
        return Str::endsWith(Str::lower($name), '.'.$extension)
            ? $name
            : $name.'.'.$extension;
    }
}
