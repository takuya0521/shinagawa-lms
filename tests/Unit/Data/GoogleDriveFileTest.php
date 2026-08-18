<?php

namespace Tests\Unit\Data;

use App\Data\GoogleDriveFile;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Google Drive APIのファイル情報を画面表示用へ変換するDTOの表示規則を確認する。
 */
final class GoogleDriveFileTest extends TestCase
{
    /**
     * Googleネイティブ形式が拡張子なしでも利用者向け名称へ変換されることを確認する。
     *
     * 前提: GoogleスプレッドシートのMIMEタイプを持つDTOを準備する。
     * 処理: `typeLabel`を実行する。
     * 期待結果: 「Google スプレッドシート」が返る。
     */
    public function test_google_native_mime_type_is_converted_to_label(): void
    {
        $file = $this->file(
            mimeType: 'application/vnd.google-apps.spreadsheet',
            size: null,
        );

        $this->assertSame('Google スプレッドシート', $file->typeLabel());
    }

    /**
     * 一般的な画像MIMEタイプが利用者向け名称へ変換されることを確認する。
     *
     * 前提: PNG画像のMIMEタイプを持つDTOを準備する。
     * 処理: `typeLabel`を実行する。
     * 期待結果: 「画像」が返る。
     */
    public function test_image_mime_type_is_converted_to_label(): void
    {
        $file = $this->file(mimeType: 'image/png');

        $this->assertSame('画像', $file->typeLabel());
    }

    /**
     * ファイルサイズが単位付き文字列へ変換され、未取得時は表示省略用のnullとなることを確認する。
     *
     * 前提: KB、MB、未取得の各サイズを持つDTOを準備する。
     * 処理: それぞれの`formattedSize`を実行する。
     * 期待結果: 2.0 KB、3.0 MB、nullが返る。
     */
    public function test_file_size_is_formatted_for_display(): void
    {
        $this->assertSame('2.0 KB', $this->file(size: 2048)->formattedSize());
        $this->assertSame(
            '3.0 MB',
            $this->file(size: 3 * 1024 * 1024)->formattedSize(),
        );
        $this->assertNull($this->file(size: null)->formattedSize());
    }

    /**
     * 各表示規則へ必要最小限の値を持つDTOを生成する。
     *
     * @param  string  $mimeType  MIMEタイプ
     * @param  int|null  $size  ファイルサイズ
     * @return GoogleDriveFile テスト対象DTO
     */
    private function file(
        string $mimeType = 'application/pdf',
        ?int $size = 1024,
    ): GoogleDriveFile {
        return new GoogleDriveFile(
            id: 'file-001',
            name: '資料',
            mimeType: $mimeType,
            modifiedAt: CarbonImmutable::parse('2026-08-05T00:30:00Z'),
            webViewLink: 'https://drive.google.com/file/d/file-001/view',
            webContentLink: null,
            iconLink: null,
            thumbnailLink: null,
            size: $size,
            description: null,
            parents: [],
            starred: false,
            trashed: false,
            ownedByMe: true,
            canEdit: true,
            canDelete: true,
            canShare: true,
            canDownload: true,
        );
    }
}
