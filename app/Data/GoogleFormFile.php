<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Google Drive上のフォームファイルを一覧表示へ渡す不変データ。
 */
final readonly class GoogleFormFile
{
    /**
     * @param  string  $id  GoogleフォームID
     * @param  string  $name  Drive上のフォーム名
     * @param  ?CarbonImmutable  $modifiedAt  最終更新日時
     * @param  ?string  $webViewLink  Google Forms編集画面URL
     * @param  bool  $ownedByMe  ログインユーザー本人が所有する場合はtrue
     * @param  bool  $canEdit  編集可能な場合はtrue
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?CarbonImmutable $modifiedAt,
        public ?string $webViewLink,
        public bool $ownedByMe,
        public bool $canEdit,
    ) {}
}
