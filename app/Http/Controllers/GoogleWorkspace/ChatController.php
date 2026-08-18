<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleChat\GoogleChatException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Services\GoogleChat\GoogleChatConversationService;
use App\Services\GoogleChat\GoogleChatReactionService;
use App\Services\GoogleChat\Support\GoogleChatConfiguration;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Google Chatのページ本体と外部APIデータ取得を分離して担当する。
 */
final class ChatController extends GoogleWorkspaceController
{
    /**
     * Google APIを待たず、Chat一覧画面の共通枠と連携状態だけを表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Chat一覧画面の軽量シェル
     */
    public function index(
        Request $request,
        GoogleChatConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $configuration->requiredScopes());

        return view('google-workspace.chat.index', $state);
    }

    /**
     * 参加中スペース一覧を取得し、Chat一覧へ差し込むHTMLを返す。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  GoogleChatConversationService  $conversationService  Chat一覧取得処理
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Chat一覧の非同期表示断片
     */
    public function indexContent(
        Request $request,
        GoogleChatConversationService $conversationService,
        GoogleChatConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $configuration->requiredScopes());
        $spaces = collect();
        $chatError = null;

        if (! $state['isConfigured'] || ! $state['isAuthorized']) {
            return $this->asyncAuthorizationRequired();
        }

        $this->prepareAsyncCache($request, $cache, $user, 'chat');

        try {
            $spaces = $conversationService->spaces($user);
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            if ($request->boolean('swr_refresh')) {
                return $this->asyncOperationFailure(
                    $exception,
                    $user,
                    'google_chat_spaces_index_swr',
                    'Google Chatの最新情報を取得できませんでした。',
                );
            }

            $this->asyncOperationFailure(
                $exception,
                $user,
                'google_chat_spaces_index_async',
                'Google Chatのスペースを取得できませんでした。',
            );
            $chatError = 'Google Chatのスペースを取得できませんでした。時間をおいて、もう一度お試しください。';
        }

        return $this->asyncView('google-workspace.chat.content', [
            'spaces' => $spaces,
            'chatError' => $chatError,
        ], $cache);
    }

    /**
     * Google APIを待たず、Chat会話画面の共通枠だけを先に表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  GoogleChatConfiguration  $configuration  Chat API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Chat会話画面の軽量シェル
     */
    public function show(
        Request $request,
        string $spaceId,
        GoogleChatConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $configuration->requiredScopes());

        return view('google-workspace.chat.show', [
            ...$state,
            'spaceId' => $spaceId,
        ]);
    }

    /**
     * Chat会話の一覧、詳細、メッセージ、固定表示と必要な補助情報を非同期取得する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  GoogleChatConversationService  $conversationService  Chat一覧・会話取得処理
     * @param  GoogleChatReactionService  $reactionService  選択メッセージのリアクション参照処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Chat会話の非同期表示断片
     */
    public function showContent(
        Request $request,
        string $spaceId,
        GoogleChatConversationService $conversationService,
        GoogleChatReactionService $reactionService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);

        $this->prepareAsyncCache($request, $cache, $user, 'chat');

        try {
            $includeMemberships = $request->string('panel')->trim()->toString() === 'members';
            $conversation = $conversationService->conversation($user, $spaceId, $includeMemberships);
            $selectedReactionMessageId = $request->string('reaction_message_id')->trim()->toString();
            $selectedReactions = $selectedReactionMessageId !== ''
                ? $reactionService->reactions($user, $spaceId, $selectedReactionMessageId)
                : collect();

            return $this->asyncView('google-workspace.chat.show-content', [
                'spaces' => $conversation->spaces,
                'space' => $conversation->space,
                'memberships' => $conversation->memberships,
                'messages' => $conversation->messages,
                'pinnedMessageNames' => $conversation->pinnedMessageNames,
                'selectedReactionMessageId' => $selectedReactionMessageId,
                'selectedReactions' => $selectedReactions,
            ], $cache);
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_chat_space_show_async',
                'Google Chatのスペース詳細を取得できませんでした。',
            );
        }
    }
}
