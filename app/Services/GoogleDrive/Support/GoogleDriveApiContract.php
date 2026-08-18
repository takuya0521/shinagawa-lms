<?php

namespace App\Services\GoogleDrive\Support;

/**
 * Google Drive APIで共通利用するfields契約を一元管理する。
 */
final class GoogleDriveApiContract
{
    public const FILE_LIST_FIELDS = 'id,name,mimeType,modifiedTime,size,webViewLink,iconLink,starred,trashed,ownedByMe';

    public const FILE_FIELDS = 'id,name,mimeType,modifiedTime,size,webViewLink,webContentLink,'
        .'iconLink,thumbnailLink,description,parents,starred,trashed,ownedByMe,'
        .'capabilities(canEdit,canDelete,canShare,canDownload)';
}
