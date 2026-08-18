<?php

namespace Tests\Unit\Data;

use App\Data\GoogleChatSpace;
use PHPUnit\Framework\TestCase;

/**
 * GoogleChatSpaceのスペース種別表示を確認する。
 */
final class GoogleChatSpaceTest extends TestCase
{
    /**
     * Google Chatの種別コードが利用者向け日本語へ変換されることを確認する。
     *
     * 前提: `GROUP_CHAT`種別のスペースDTOを準備する。
     * 処理: `typeLabel`を実行する。
     * 期待結果: 「グループチャット」が返る。
     */
    public function test_type_label_translates_group_chat(): void
    {
        $space = new GoogleChatSpace(
            id: 'AAAABBBBCCCC',
            resourceName: 'spaces/AAAABBBBCCCC',
            displayName: '学習相談',
            type: 'GROUP_CHAT',
            spaceUri: null,
            description: null,
            historyState: 'HISTORY_ON',
            membershipState: 'JOINED',
            canManageMembers: true,
        );

        self::assertSame('グループチャット', $space->typeLabel());
    }
}
