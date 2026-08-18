<?php

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Google Drive詳細画面で使用するファイル・共有・コメント・版履歴をまとめる不変データ。
 */
final readonly class GoogleDriveDetail
{
    /**
     * @param  GoogleDriveFile  $file  対象ファイルの基本情報
     * @param  Collection<int, GoogleDrivePermission>  $permissions  対象ファイルの共有権限一覧
     * @param  Collection<int, GoogleDriveComment>  $comments  対象ファイルのコメント一覧
     * @param  Collection<int, GoogleDriveRevision>  $revisions  対象ファイルの版履歴一覧
     */
    public function __construct(
        public GoogleDriveFile $file,
        public Collection $permissions,
        public Collection $comments,
        public Collection $revisions,
    ) {}
}
