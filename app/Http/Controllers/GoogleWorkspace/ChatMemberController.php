<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleChat\GoogleChatException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\ChatMemberRequest;
use App\Services\GoogleChat\GoogleChatMembershipService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Chatスペースのメンバー追加・削除を担当する。
 */
final class ChatMemberController extends GoogleWorkspaceController
{
    /**
     * Google Chatスペースへメンバーを追加または招待する。
     *
     * @param  ChatMemberRequest  $request  検証済みメンバー入力
     * @param  string  $spaceId  Google ChatスペースID
     * @param  GoogleChatMembershipService  $service  Chatメンバー処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function store(
        ChatMemberRequest $request,
        string $spaceId,
        GoogleChatMembershipService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->addMember($user, $spaceId, $request->email());
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_member_create',
                'Google Chatスペースへメンバーを追加できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatスペースへメンバーを追加しました。');
    }

    /**
     * Google Chatスペースからメンバーを削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $membershipId  メンバーシップID
     * @param  GoogleChatMembershipService  $service  Chatメンバー処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $spaceId,
        string $membershipId,
        GoogleChatMembershipService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->removeMember($user, $spaceId, $membershipId);
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_member_delete',
                'Google Chatスペースからメンバーを削除できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatスペースからメンバーを削除しました。');
    }
}
