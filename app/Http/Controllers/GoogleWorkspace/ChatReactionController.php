<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleChat\GoogleChatException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\ChatReactionRequest;
use App\Services\GoogleChat\GoogleChatReactionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Chatメッセージのリアクション追加・削除を担当する。
 */
final class ChatReactionController extends GoogleWorkspaceController
{
    /**
     * Google Chatメッセージへリアクションを追加する。
     *
     * @param  ChatReactionRequest  $request  検証済みリアクション入力
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  GoogleChatReactionService  $service  Chatリアクション処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function store(
        ChatReactionRequest $request,
        string $spaceId,
        string $messageId,
        GoogleChatReactionService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->addReaction($user, $spaceId, $messageId, $request->emoji());
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_reaction_create',
                'Google Chatのリアクションを追加できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatのリアクションを追加しました。');
    }

    /**
     * Google Chatメッセージのリアクションを削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  string  $reactionId  Google ChatリアクションID
     * @param  GoogleChatReactionService  $service  Chatリアクション処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $spaceId,
        string $messageId,
        string $reactionId,
        GoogleChatReactionService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->removeReaction($user, $spaceId, $messageId, $reactionId);
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_reaction_delete',
                'Google Chatのリアクションを削除できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatのリアクションを削除しました。');
    }
}
