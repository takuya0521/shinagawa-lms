<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleChat\GoogleChatException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\ChatMessageRequest;
use App\Services\GoogleChat\GoogleChatMessageService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Chatメッセージの投稿・更新・削除を担当する。
 */
final class ChatMessageController extends GoogleWorkspaceController
{
    /**
     * Google Chatへ新規メッセージまたはスレッド返信を投稿する。
     *
     * @param  ChatMessageRequest  $request  検証済みメッセージ入力
     * @param  string  $spaceId  Google ChatスペースID
     * @param  GoogleChatMessageService  $service  Chatメッセージ処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function store(
        ChatMessageRequest $request,
        string $spaceId,
        GoogleChatMessageService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->createMessage(
                $user,
                $spaceId,
                $request->text(),
                $request->threadName(),
                $request->attachment(),
            );
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_message_create',
                'Google Chatへメッセージを投稿できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatへメッセージを投稿しました。');
    }

    /**
     * 自分が投稿したGoogle Chatメッセージ本文を更新する。
     *
     * @param  ChatMessageRequest  $request  検証済みメッセージ入力
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  GoogleChatMessageService  $service  Chatメッセージ処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function update(
        ChatMessageRequest $request,
        string $spaceId,
        string $messageId,
        GoogleChatMessageService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->updateMessage($user, $spaceId, $messageId, $request->text());
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_message_update',
                'Google Chatのメッセージを更新できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatのメッセージを更新しました。');
    }

    /**
     * 自分が投稿したGoogle Chatメッセージを削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  GoogleChatMessageService  $service  Chatメッセージ処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $spaceId,
        string $messageId,
        GoogleChatMessageService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->deleteMessage($user, $spaceId, $messageId, $request->boolean('force'));
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_message_delete',
                'Google Chatのメッセージを削除できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatのメッセージを削除しました。');
    }
}
