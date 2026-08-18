<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Google Drive APIのファイル情報を、画面へ安全に渡す不変データへ変換する。
 *
 * API固有の配列をBladeから切り離し、操作可否をGoogle側のcapabilitiesに従って表示する。
 */
final readonly class GoogleDriveFile
{
    private const BYTES_PER_KIBIBYTE = 1024;

    /**
     * @param  string  $id  Google Drive上のファイルID
     * @param  string  $name  ファイル名
     * @param  string  $mimeType  MIMEタイプ
     * @param  ?CarbonImmutable  $modifiedAt  最終更新日時
     * @param  ?string  $webViewLink  Google Drive上で開くURL
     * @param  ?string  $webContentLink  バイナリファイルのダウンロードURL
     * @param  ?string  $iconLink  Google提供のファイルアイコンURL
     * @param  ?string  $thumbnailLink  Google提供のサムネイルURL
     * @param  ?int  $size  ファイルサイズ。Googleネイティブ形式ではnull
     * @param  ?string  $description  ファイル説明
     * @param  list<string>  $parents  親フォルダID一覧
     * @param  bool  $starred  スター付きかどうか
     * @param  bool  $trashed  ゴミ箱内かどうか
     * @param  bool  $ownedByMe  本人所有かどうか
     * @param  bool  $canEdit  メタデータ編集が可能かどうか
     * @param  bool  $canDelete  完全削除が可能かどうか
     * @param  bool  $canShare  共有設定が可能かどうか
     * @param  bool  $canDownload  ダウンロードまたはエクスポートが可能かどうか
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $mimeType,
        public ?CarbonImmutable $modifiedAt,
        public ?string $webViewLink,
        public ?string $webContentLink,
        public ?string $iconLink,
        public ?string $thumbnailLink,
        public ?int $size,
        public ?string $description,
        public array $parents,
        public bool $starred,
        public bool $trashed,
        public bool $ownedByMe,
        public bool $canEdit,
        public bool $canDelete,
        public bool $canShare,
        public bool $canDownload,
    ) {}

    /**
     * フォルダかどうかを返す。
     *
     * @return bool フォルダの場合はtrue
     */
    public function isFolder(): bool
    {
        return $this->mimeType === 'application/vnd.google-apps.folder';
    }

    /**
     * Google Workspace形式かどうかを返す。
     *
     * @return bool Google Workspace形式の場合はtrue
     */
    public function isGoogleWorkspaceFile(): bool
    {
        return str_starts_with($this->mimeType, 'application/vnd.google-apps.')
            && ! $this->isFolder();
    }

    /**
     * MIMEタイプを利用者向けの日本語種別へ変換する。
     *
     * @return string 画面表示用のファイル種別
     */
    public function typeLabel(): string
    {
        if (str_starts_with($this->mimeType, 'image/')) {
            return '画像';
        }

        if (str_starts_with($this->mimeType, 'video/')) {
            return '動画';
        }

        if (str_starts_with($this->mimeType, 'audio/')) {
            return '音声';
        }

        return match ($this->mimeType) {
            'application/vnd.google-apps.folder' => 'フォルダ',
            'application/vnd.google-apps.document' => 'Google ドキュメント',
            'application/vnd.google-apps.spreadsheet' => 'Google スプレッドシート',
            'application/vnd.google-apps.presentation' => 'Google スライド',
            'application/vnd.google-apps.form' => 'Google フォーム',
            'application/vnd.google-apps.drawing' => 'Google 図形描画',
            'application/vnd.google-apps.shortcut' => 'ショートカット',
            'application/pdf' => 'PDF',
            'application/zip',
            'application/x-zip-compressed' => 'ZIP',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint',
            default => 'ファイル',
        };
    }

    /**
     * ファイルサイズを読みやすい単位へ変換する。
     *
     * @return ?string 単位付きファイルサイズ。サイズがない場合はnull
     */
    public function formattedSize(): ?string
    {
        if ($this->size === null) {
            return null;
        }

        if ($this->size < self::BYTES_PER_KIBIBYTE) {
            return $this->size.' B';
        }

        if ($this->size < self::BYTES_PER_KIBIBYTE ** 2) {
            return number_format($this->size / self::BYTES_PER_KIBIBYTE, 1).' KB';
        }

        if ($this->size < self::BYTES_PER_KIBIBYTE ** 3) {
            return number_format($this->size / (self::BYTES_PER_KIBIBYTE ** 2), 1).' MB';
        }

        return number_format($this->size / (self::BYTES_PER_KIBIBYTE ** 3), 1).' GB';
    }
}
