<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleClassroom\GoogleClassroomException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\ClassroomIndexRequest;
use App\Services\GoogleClassroom\GoogleClassroomService;
use App\Services\GoogleClassroom\Support\GoogleClassroomConfiguration;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Google Classroomの軽量シェル、クラス一覧、クラス詳細を担当する。
 */
final class ClassroomController extends GoogleWorkspaceController
{
    /**
     * Google APIを待たず、Classroom画面の共通枠と連携状態だけを表示する。
     *
     * @param  ClassroomIndexRequest  $request  検索・状態条件付きHTTPリクエスト
     * @param  GoogleClassroomConfiguration  $configuration  Classroom API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Classroom一覧画面の軽量シェル
     */
    public function index(
        ClassroomIndexRequest $request,
        GoogleClassroomConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState(
            $user,
            $workspaceService,
            $configuration->requiredScopes(),
        );

        return view('google-workspace.classroom.index', [
            ...$state,
            'keyword' => $request->keyword(),
            'courseState' => $request->state(),
        ]);
    }

    /**
     * Classroomクラス一覧を取得し、非同期表示断片として返す。
     *
     * @param  ClassroomIndexRequest  $request  検索・状態条件付きHTTPリクエスト
     * @param  GoogleClassroomService  $classroomService  Classroom API処理
     * @param  GoogleClassroomConfiguration  $configuration  Classroom API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Classroom一覧の非同期表示断片
     */
    public function indexContent(
        ClassroomIndexRequest $request,
        GoogleClassroomService $classroomService,
        GoogleClassroomConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $connection = $user->googleDriveConnection;

        if ($connection === null
            || ! $workspaceService->hasScopes($connection, $configuration->requiredScopes())) {
            return $this->asyncAuthorizationRequired();
        }

        $this->prepareAsyncCache($request, $cache, $user, 'classroom');
        $keyword = $request->keyword();
        $courseState = $request->state();
        $cacheKey = sprintf('courses:%s:%s', $courseState, $keyword ?? '');

        try {
            $courses = $cache->remember(
                $user,
                'classroom',
                $cacheKey,
                300,
                fn () => $classroomService->courses($user, $keyword, $courseState),
            );
        } catch (ConnectionException|RequestException|GoogleClassroomException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_classroom_index_async',
                $this->classroomFailureMessage($exception, 'Google Classroomのクラス一覧を取得できませんでした。'),
            );
        }

        return $this->asyncView('google-workspace.classroom.content', [
            'courses' => $courses,
            'keyword' => $keyword,
            'courseState' => $courseState,
        ], $cache);
    }

    /**
     * Google APIを待たず、Classroomクラス詳細の共通枠だけを表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $courseId  ClassroomクラスID
     * @param  GoogleClassroomConfiguration  $configuration  Classroom API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Classroomクラス詳細の軽量シェル
     */
    public function show(
        Request $request,
        string $courseId,
        GoogleClassroomConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState(
            $user,
            $workspaceService,
            $configuration->requiredScopes(),
        );

        return view('google-workspace.classroom.show', [
            ...$state,
            'courseId' => $courseId,
        ]);
    }

    /**
     * Classroomクラス詳細、課題、お知らせを取得して非同期表示断片として返す。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $courseId  ClassroomクラスID
     * @param  GoogleClassroomService  $classroomService  Classroom API処理
     * @param  GoogleClassroomConfiguration  $configuration  Classroom API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Classroom詳細の非同期表示断片
     */
    public function showContent(
        Request $request,
        string $courseId,
        GoogleClassroomService $classroomService,
        GoogleClassroomConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $connection = $user->googleDriveConnection;

        if ($connection === null
            || ! $workspaceService->hasScopes($connection, $configuration->requiredScopes())) {
            return $this->asyncAuthorizationRequired();
        }

        $this->prepareAsyncCache($request, $cache, $user, 'classroom');

        try {
            $detail = $cache->remember(
                $user,
                'classroom',
                'detail:'.$courseId,
                120,
                fn () => $classroomService->courseDetail($user, $courseId),
            );
        } catch (ConnectionException|RequestException|GoogleClassroomException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_classroom_show_async',
                $this->classroomFailureMessage($exception, 'Google Classroomのクラス詳細を取得できませんでした。'),
            );
        }

        return $this->asyncView('google-workspace.classroom.show-content', [
            'detail' => $detail,
        ], $cache);
    }

    /**
     * Classroom APIの代表的なHTTPエラーを利用者向けメッセージへ変換する。
     *
     * @param  mixed  $exception  捕捉した外部API例外
     * @param  string  $fallback  通常のエラーメッセージ
     * @return string 利用者へ表示するメッセージ
     */
    private function classroomFailureMessage(mixed $exception, string $fallback): string
    {
        if (! $exception instanceof RequestException) {
            return $fallback;
        }

        return match ($exception->response->status()) {
            403 => 'Google Classroom APIの有効化、Classroom利用権限、OAuth再承認を確認してください。',
            404 => '指定したGoogle Classroomのクラスが見つからないか、閲覧権限がありません。',
            429 => 'Google Classroom APIの利用上限に達しました。時間をおいて再度お試しください。',
            default => $fallback,
        };
    }
}
