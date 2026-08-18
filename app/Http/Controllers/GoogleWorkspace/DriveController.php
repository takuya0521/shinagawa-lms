<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Data\GoogleDrivePage;
use App\Exceptions\GoogleDrive\GoogleDriveException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\DriveIndexRequest;
use App\Services\GoogleDrive\GoogleDriveDetailService;
use App\Services\GoogleDrive\GoogleDriveOverviewService;
use App\Services\GoogleDrive\GoogleDriveQueryService;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Google Driveの一覧・詳細ページ本体と非同期データ取得を分離して担当する。
 */
final class DriveController extends GoogleWorkspaceController
{
    /**
     * Google APIを待たず、Drive画面の共通枠と連携状態だけを先に表示する。
     *
     * @param  DriveIndexRequest  $request  検証済み一覧条件
     * @param  GoogleDriveQueryService  $queryService  Drive設定・スコープ参照処理
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Drive一覧画面の軽量シェル
     */
    public function index(
        DriveIndexRequest $request,
        GoogleDriveQueryService $queryService,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $queryService->requiredScopes());

        return view('google-workspace.drive.index', [
            ...$state,
            'parentId' => $request->parentId(),
            'keyword' => $request->keyword(),
            'viewMode' => $request->viewMode(),
            'driveId' => $request->driveId(),
        ]);
    }

    /**
     * Drive一覧のGoogle APIデータだけを取得し、差し込み用HTMLとして返す。
     *
     * @param  DriveIndexRequest  $request  検証済み一覧条件
     * @param  GoogleDriveOverviewService  $overviewService  一覧画面用の並列取得処理
     * @param  GoogleDriveQueryService  $queryService  Drive設定・スコープ参照処理
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Drive一覧の非同期表示断片
     */
    public function indexContent(
        DriveIndexRequest $request,
        GoogleDriveOverviewService $overviewService,
        GoogleDriveQueryService $queryService,
        GoogleWorkspaceService $workspaceService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $queryService->requiredScopes());
        $page = new GoogleDrivePage(collect(), null);
        $sharedDrives = collect();
        $driveError = null;

        if (! $state['isConfigured'] || ! $state['isAuthorized']) {
            return $this->asyncAuthorizationRequired();
        }

        $this->prepareAsyncCache($request, $cache, $user, 'drive');

        try {
            $overview = $overviewService->overview(
                $user,
                $request->parentId(),
                $request->keyword(),
                $request->pageToken(),
                $request->viewMode(),
                $request->driveId(),
            );
            $page = $overview->page;
            $sharedDrives = $overview->sharedDrives;
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            if ($request->boolean('swr_refresh')) {
                return $this->asyncOperationFailure(
                    $exception,
                    $user,
                    'google_drive_files_index_swr',
                    'Google Driveの最新情報を取得できませんでした。',
                );
            }

            $this->asyncOperationFailure(
                $exception,
                $user,
                'google_drive_files_index_async',
                'Google Driveの項目を取得できませんでした。時間をおいて、もう一度お試しください。',
            );
            $driveError = 'Google Driveの項目を取得できませんでした。時間をおいて、もう一度お試しください。';
        }

        return $this->asyncView('google-workspace.drive.content', [
            'files' => $page->files,
            'nextPageToken' => $page->nextPageToken,
            'parentId' => $request->parentId(),
            'keyword' => $request->keyword(),
            'showTrash' => $request->viewMode() === 'trash',
            'viewMode' => $request->viewMode(),
            'driveId' => $request->driveId(),
            'writeParentId' => $request->parentId()
                ?? ($request->viewMode() === 'shared-drive' ? $request->driveId() : null),
            'sharedDrives' => $sharedDrives,
            'driveError' => $driveError,
        ], $cache);
    }

    /**
     * Google APIを待たず、Driveファイル詳細画面の枠だけを先に表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveQueryService  $queryService  Drive設定・スコープ参照処理
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Drive詳細画面の軽量シェル
     */
    public function show(
        Request $request,
        string $fileId,
        GoogleDriveQueryService $queryService,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $queryService->requiredScopes());

        return view('google-workspace.drive.show', [
            ...$state,
            'fileId' => $fileId,
        ]);
    }

    /**
     * Driveファイル詳細、共有、コメント、版履歴を取得して差し込み用HTMLを返す。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveDetailService  $detailService  詳細画面用の並列取得処理
     * @param  GoogleWorkspaceCache  $cache  SWR表示とバックグラウンド再検証に使用するキャッシュ
     * @return Response Drive詳細の非同期表示断片
     */
    public function showContent(
        Request $request,
        string $fileId,
        GoogleDriveDetailService $detailService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $this->prepareAsyncCache($request, $cache, $user, 'drive');

        try {
            $detail = $detailService->detail($user, $fileId);

            return $this->asyncView('google-workspace.drive.show-content', [
                'file' => $detail->file,
                'permissions' => $detail->permissions,
                'comments' => $detail->comments,
                'revisions' => $detail->revisions,
            ], $cache);
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_drive_file_show_async',
                'Google Driveのファイル詳細を取得できませんでした。',
            );
        }
    }
}
