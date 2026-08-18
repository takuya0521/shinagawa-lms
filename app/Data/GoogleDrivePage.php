<?php

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Google Driveのページング済みファイル一覧を画面へ渡す不変データ。
 */
final readonly class GoogleDrivePage
{
    /**
     * @param  Collection<int, GoogleDriveFile>  $files  ファイル一覧
     * @param  ?string  $nextPageToken  次ページ取得トークン
     */
    public function __construct(
        public Collection $files,
        public ?string $nextPageToken,
    ) {}
}
