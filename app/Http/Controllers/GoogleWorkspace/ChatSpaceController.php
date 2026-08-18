<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleChat\GoogleChatException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\ChatSpaceRequest;
use App\Services\GoogleChat\GoogleChatSpaceService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Chatスペースの作成・更新・削除を担当する。
 */
final class ChatSpaceController extends GoogleWorkspaceController
{
    /**
     * 名前付きスペース、グループチャット、ダイレクトメッセージを作成する。
     *
     * @param  ChatSpaceRequest  $request  検証済みスペース入力
     * @param  GoogleChatSpaceService  $service  Chatスペース処理
     * @return RedirectResponse スペース一覧へのリダイレクト
     */
    public function store(
        ChatSpaceRequest $request,
        GoogleChatSpaceService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $space = $service->setupSpace(
                $user,
                $request->spaceType(),
                $request->displayName(),
                $request->description(),
                $request->memberEmails(),
            );
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_space_create',
                'Google Chatの会話を作成できませんでした。Chatアプリ設定、メンバー、権限を確認してください。',
            );
        }

        return redirect()
            ->route('google-workspace.chat.show', $space->id)
            ->with('success', 'Google Chatの会話を作成しました。');
    }

    /**
     * Google Chatスペースの名称と説明を更新する。
     *
     * @param  ChatSpaceRequest  $request  検証済みスペース入力
     * @param  string  $spaceId  Google ChatスペースID
     * @param  GoogleChatSpaceService  $service  Chatスペース処理
     * @return RedirectResponse スペース詳細へのリダイレクト
     */
    public function update(
        ChatSpaceRequest $request,
        string $spaceId,
        GoogleChatSpaceService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->updateSpace(
                $user,
                $spaceId,
                $request->displayName(),
                $request->description(),
            );
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_space_update',
                'Google Chatスペースを更新できませんでした。',
            );
        }

        return back()->with('success', 'Google Chatスペースを更新しました。');
    }

    /**
     * 名前付きGoogle Chatスペースを完全削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  GoogleChatSpaceService  $service  Chatスペース処理
     * @return RedirectResponse スペース一覧へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $spaceId,
        GoogleChatSpaceService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->deleteSpace($user, $spaceId);
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_space_delete',
                'Google Chatスペースを削除できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.chat.index')
            ->with('success', 'Google Chatスペースを削除しました。');
    }
}
