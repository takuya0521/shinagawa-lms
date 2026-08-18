<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleChat\GoogleChatException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Services\GoogleChat\GoogleChatPinService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Chatメッセージの固定表示・解除を担当する。
 */
final class ChatPinController extends GoogleWorkspaceController
{
    /**
     * Google Chatメッセージをスペースへ固定表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  GoogleChatPinService  $service  Chat固定表示処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function store(
        Request $request,
        string $spaceId,
        string $messageId,
        GoogleChatPinService $service,
    ): RedirectResponse {
        return $this->toggle($request, $spaceId, $messageId, $service, true);
    }

    /**
     * Google Chatメッセージの固定表示を解除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  GoogleChatPinService  $service  Chat固定表示処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $spaceId,
        string $messageId,
        GoogleChatPinService $service,
    ): RedirectResponse {
        return $this->toggle($request, $spaceId, $messageId, $service, false);
    }

    /**
     * Google Chatメッセージの固定状態を切り替える。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  GoogleChatPinService  $service  Chat固定表示処理
     * @param  bool  $pin  固定する場合はtrue
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    private function toggle(
        Request $request,
        string $spaceId,
        string $messageId,
        GoogleChatPinService $service,
        bool $pin,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $pin
                ? $service->pinMessage($user, $spaceId, $messageId)
                : $service->unpinMessage($user, $spaceId, $messageId);
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                $pin ? 'google_chat_message_pin' : 'google_chat_message_unpin',
                $pin
                    ? 'Google Chatのメッセージを固定できませんでした。'
                    : 'Google Chatのメッセージ固定を解除できませんでした。',
            );
        }

        return back()->with(
            'success',
            $pin
                ? 'Google Chatのメッセージを固定しました。'
                : 'Google Chatのメッセージ固定を解除しました。',
        );
    }
}
