<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Google Driveの版情報を画面へ渡す不変データ。
 */
final readonly class GoogleDriveRevision
{
    /**
     * @param  string  $id  版ID
     * @param  ?CarbonImmutable  $modifiedAt  更新日時
     * @param  ?string  $modifierName  更新者名
     * @param  ?int  $size  版のサイズ
     * @param  bool  $keepForever  永久保持かどうか
     */
    public function __construct(
        public string $id,
        public ?CarbonImmutable $modifiedAt,
        public ?string $modifierName,
        public ?int $size,
        public bool $keepForever,
    ) {}
}
