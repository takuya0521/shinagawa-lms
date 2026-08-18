<?php

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Google Chat会話画面で同時に使用するスペース・メンバー・メッセージをまとめる不変データ。
 */
final readonly class GoogleChatConversation
{
    /**
     * @param  Collection<int, GoogleChatSpace>  $spaces  利用者が参加しているスペース一覧
     * @param  GoogleChatSpace  $space  表示対象スペースの詳細
     * @param  Collection<int, GoogleChatMembership>  $memberships  表示対象スペースのメンバー一覧
     * @param  Collection<int, GoogleChatMessage>  $messages  表示対象スペースのメッセージ一覧
     * @param  list<string>  $pinnedMessageNames  固定表示されているメッセージのリソース名
     */
    public function __construct(
        public Collection $spaces,
        public GoogleChatSpace $space,
        public Collection $memberships,
        public Collection $messages,
        public array $pinnedMessageNames,
    ) {}
}
