<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleMeet\GoogleMeetException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\MeetIndexRequest;
use App\Services\GoogleMeet\GoogleMeetService;
use App\Services\GoogleMeet\Support\GoogleMeetConfiguration;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Google Meetの軽量シェル、会議スペース作成、会議履歴の非同期表示を担当する。
 */
final class MeetController extends GoogleWorkspaceController
{
    /**
     * Google APIを待たず、Meet画面の共通枠と連携状態だけを表示する。
     *
     * @param  MeetIndexRequest  $request  検索条件付きHTTPリクエスト
     * @param  GoogleMeetConfiguration  $configuration  Meet API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Meet一覧画面の軽量シェル
     */
    public function index(
        MeetIndexRequest $request,
        GoogleMeetConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState(
            $user,
            $workspaceService,
            $configuration->requiredScopes(),
        );

        return view('google-workspace.meet.index', [
            ...$state,
            'meetingCode' => $request->meetingCode(),
            'createdSpace' => $request->session()->get('google_meet_created_space'),
        ]);
    }

    /**
     * 検索したスペースと直近の会議履歴を取得し、Meet画面へ差し込むHTMLを返す。
     *
     * @param  MeetIndexRequest  $request  検索条件付きHTTPリクエスト
     * @param  GoogleMeetService  $meetService  Meet API処理
     * @param  GoogleMeetConfiguration  $configuration  Meet API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Meet画面の非同期表示断片
     */
    public function indexContent(
        MeetIndexRequest $request,
        GoogleMeetService $meetService,
        GoogleMeetConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $state = $this->connectionState(
            $user,
            $workspaceService,
            $configuration->requiredScopes(),
        );

        if (! $state['isConfigured'] || ! $state['isAuthorized']) {
            return $this->asyncAuthorizationRequired();
        }

        $this->prepareAsyncCache($request, $cache, $user, 'meet');
        $meetingCode = $request->meetingCode();

        try {
            $space = $meetingCode !== null
                ? $cache->remember(
                    $user,
                    'meet',
                    'space:'.$meetingCode,
                    60,
                    fn () => $meetService->space($user, $meetingCode),
                )
                : null;
            $conferences = $cache->remember(
                $user,
                'meet',
                'conferences:'.($meetingCode ?? 'recent'),
                60,
                fn () => $meetService->conferences($user, $meetingCode),
            );
        } catch (ConnectionException|RequestException|GoogleMeetException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_meet_index_async',
                'Google Meetの情報を取得できませんでした。',
            );
        }

        return $this->asyncView('google-workspace.meet.content', [
            'meetingCode' => $meetingCode,
            'space' => $space,
            'conferences' => $conferences,
        ], $cache);
    }

    /**
     * ログインユーザー名義で新しいGoogle Meetスペースを作成する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  GoogleMeetService  $meetService  Meet API処理
     * @return RedirectResponse 作成後のMeet一覧画面
     */
    public function store(Request $request, GoogleMeetService $meetService): RedirectResponse
    {
        $user = $this->resolveUser($request);

        try {
            $space = $meetService->createSpace($user);
        } catch (ConnectionException|RequestException|GoogleMeetException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_meet_space_create',
                'Google Meetの会議を作成できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.meet.index')
            ->with('success', 'Google Meetの会議を作成しました。')
            ->with('google_meet_created_space', [
                'meeting_code' => $space->meetingCode,
                'meeting_uri' => $space->meetingUri,
            ]);
    }
}
