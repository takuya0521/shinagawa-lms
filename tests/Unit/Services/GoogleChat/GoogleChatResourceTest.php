<?php

namespace Tests\Unit\Services\GoogleChat;

use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Services\GoogleChat\Support\GoogleChatResource;
use PHPUnit\Framework\TestCase;

/**
 * Google Chatリソース名の生成と所属スペース検証を確認する。
 */
final class GoogleChatResourceTest extends TestCase
{
    /**
     * スペースIDとメッセージIDから正しいGoogle Chatリソース名を生成できることを確認する。
     *
     * 前提: 許可文字だけで構成されたスペースIDとメッセージIDを準備する。
     * 処理: `messageName`を実行する。
     * 期待結果: spaces/{space}/messages/{message}形式の文字列が返る。
     */
    public function test_message_name_builds_resource_path(): void
    {
        $resource = new GoogleChatResource;

        self::assertSame(
            'spaces/space-001/messages/message_002',
            $resource->messageName('space-001', 'message_002'),
        );
    }

    /**
     * 別スペースのスレッドを返信先として使用できないことを確認する。
     *
     * 前提: 選択中とは異なるスペースのスレッドリソース名を準備する。
     * 処理: `validatedThreadName`を実行する。
     * 期待結果: 別スペース参照としてGoogleChatResponseExceptionが送出される。
     */
    public function test_validated_thread_name_rejects_other_space(): void
    {
        $resource = new GoogleChatResource;

        $this->expectException(GoogleChatResponseException::class);
        $resource->validatedThreadName('space-001', 'spaces/space-999/threads/thread-001');
    }

    /**
     * URLへ連結できない文字を含むリソースIDを拒否することを確認する。
     *
     * 前提: スラッシュを含む不正なリソースIDを準備する。
     * 処理: `validatedId`を実行する。
     * 期待結果: GoogleChatResponseExceptionが送出される。
     */
    public function test_validated_id_rejects_path_separator(): void
    {
        $resource = new GoogleChatResource;

        $this->expectException(GoogleChatResponseException::class);
        $resource->validatedId('space/other');
    }
}
