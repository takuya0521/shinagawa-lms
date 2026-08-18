<?php

namespace App\Data;

/**
 * Google Drive APIの共有ドライブを画面表示へ渡す不変データ。
 */
final readonly class GoogleSharedDrive
{
    /**
     * @param  string  $id  共有ドライブID
     * @param  string  $name  共有ドライブ名
     */
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
