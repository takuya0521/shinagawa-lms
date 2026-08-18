<?php

namespace App\Data;

/**
 * Google Chatメッセージのリアクションを画面へ渡す不変データ。
 */
final readonly class GoogleChatReaction
{
    /**
     * @param  string  $resourceName  リアクションのリソース名
     * @param  string  $emoji  Unicode絵文字またはカスタム絵文字名
     * @param  ?string  $userName  反応したユーザーのリソース名
     */
    public function __construct(
        public string $resourceName,
        public string $emoji,
        public ?string $userName,
    ) {}
}
