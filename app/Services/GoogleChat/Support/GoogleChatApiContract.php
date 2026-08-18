<?php

namespace App\Services\GoogleChat\Support;

/**
 * Google Chat APIで使用するfields指定を一元管理する。
 * 一覧APIでは取得できない項目を混在させるとリクエスト全体が400になるため、
 * APIごとの返却契約を定数として固定し、各Serviceの独自指定を防止する。
 */
final class GoogleChatApiContract
{
    public const SPACE_LIST_FIELDS = 'spaces(name,displayName,spaceType,spaceUri,spaceDetails),nextPageToken';

    public const SPACE_DETAIL_FIELDS = 'name,displayName,spaceType,spaceUri,'
        .'spaceDetails,spaceHistoryState,permissionSettings';

    public const MEMBERSHIP_FIELDS = 'memberships(name,state,role,member(name,displayName,type,email)),nextPageToken';

    public const MESSAGE_FIELDS = 'messages('
        .'name,text,createTime,lastUpdateTime,deleteTime,sender(name,displayName),thread(name),'
        .'emojiReactionSummaries,attachment(name,contentName,contentType,downloadUri)'
        .'),nextPageToken';

    public const MESSAGE_ATTACHMENT_FIELDS = 'attachment('
        .'name,contentName,contentType,source,attachmentDataRef(resourceName),driveDataRef(driveFileId)'
        .')';

    /**
     * APIごとのfields定数だけを提供し、状態を持たせないためインスタンス化を禁止する。
     */
    private function __construct() {}
}
