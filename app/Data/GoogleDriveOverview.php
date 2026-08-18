<?php

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Google Drive一覧画面で同時に使用するファイル一覧と共有ドライブをまとめる不変データ。
 */
final readonly class GoogleDriveOverview
{
    /**
     * @param  GoogleDrivePage  $page  表示条件に一致したファイル一覧と次ページトークン
     * @param  Collection<int, GoogleSharedDrive>  $sharedDrives  利用者が参照できる共有ドライブ一覧
     */
    public function __construct(
        public GoogleDrivePage $page,
        public Collection $sharedDrives,
    ) {}
}
