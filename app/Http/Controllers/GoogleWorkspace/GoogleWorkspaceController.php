<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Http\Controllers\Controller;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google Workspace各画面で共通利用する認証主体確認と安全な例外変換を提供する。
 */
abstract class GoogleWorkspaceController extends Controller
{
    /**
     * 認証Middleware通過後もUser型を明示確認し、外部APIへ不正な主体を渡さない。
     *
     * @param  Request  $request  HTTPリクエスト
     * @return User 認証済みユーザー
     */
    protected function resolveUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Google Workspace共通連携状態を画面へ渡す配列として返す。
     *
     * @param  User  $user  認証済みユーザー
     * @param  GoogleWorkspaceService  $service  共通OAuth処理
     * @param  list<string>  $requiredScopes  画面で必要なスコープ
     * @return array{
     *     isConfigured: bool,
     *     isConnected: bool,
     *     isAuthorized: bool,
     *     connection: GoogleDriveConnection|null
     * } 連携状態
     */
    protected function connectionState(
        User $user,
        GoogleWorkspaceService $service,
        array $requiredScopes,
    ): array {
        $connection = $user->googleDriveConnection;
        $isConnected = $connection instanceof GoogleDriveConnection;

        return [
            'isConfigured' => $service->isConfigured(),
            'isConnected' => $isConnected,
            'isAuthorized' => $isConnected && $service->hasScopes($connection, $requiredScopes),
            'connection' => $connection,
        ];
    }

    /**
     * 外部API例外を機密情報を含まないログと利用者向けメッセージへ変換する。
     *
     * @param  Throwable  $exception  捕捉した外部API例外
     * @param  User  $user  操作ユーザー
     * @param  string  $operation  ログ用操作名
     * @param  string  $message  利用者向けメッセージ
     * @return RedirectResponse 直前画面へのリダイレクト
     */
    protected function operationFailure(
        Throwable $exception,
        User $user,
        string $operation,
        string $message,
    ): RedirectResponse {
        $this->logOperationFailure(
            'Google Workspace API操作に失敗しました。',
            $exception,
            $user,
            $operation,
        );

        return back()->withInput()->with('error', $message);
    }

    /**
     * 非同期表示用Google API例外をログへ残し、画面内エラー断片へ変換する。
     *
     * @param  Throwable  $exception  捕捉した外部API例外
     * @param  User  $user  操作ユーザー
     * @param  string  $operation  ログ用操作名
     * @param  string  $message  利用者向けメッセージ
     * @return Response Googleデータ領域へ表示するエラー断片
     */
    protected function asyncOperationFailure(
        Throwable $exception,
        User $user,
        string $operation,
        string $message,
    ): Response {
        $this->logOperationFailure(
            'Google Workspace API非同期取得に失敗しました。',
            $exception,
            $user,
            $operation,
        );

        return response()
            ->view('google-workspace._async-error', ['message' => $message])
            ->header('X-Google-Workspace-Async-Error', '1')
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Google Workspace連携または必要スコープが不足している場合の非同期エラーを返す。
     *
     * @return Response Googleデータ領域へ表示する認可エラー断片
     */
    protected function asyncAuthorizationRequired(): Response
    {
        return response()
            ->view('google-workspace._async-error', [
                'message' => 'Google Workspaceの連携または追加権限の承認が必要です。',
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * SWR再取得か手動更新かを判定し、キャッシュ読込方針を設定する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  GoogleWorkspaceCache  $cache  Google Workspaceキャッシュ
     * @param  User  $user  認証済み利用者
     * @param  string  $service  drive、calendar、chat、meet、formsのいずれか
     */
    protected function prepareAsyncCache(
        Request $request,
        GoogleWorkspaceCache $cache,
        User $user,
        string $service,
    ): void {
        if ($request->boolean('refresh')) {
            $cache->invalidate($user, $service);

            return;
        }

        if ($request->boolean('swr_refresh')) {
            $cache->requireFresh();
        }
    }

    /**
     * 非同期HTMLへSWR状態ヘッダーを付け、ブラウザ側の再検証判断を可能にする。
     *
     * @param  string  $view  Bladeビュー名
     * @param  array<string, mixed>  $data  ビューへ渡す値
     * @param  GoogleWorkspaceCache  $cache  Google Workspaceキャッシュ
     * @return Response 非同期HTMLレスポンス
     */
    protected function asyncView(string $view, array $data, GoogleWorkspaceCache $cache): Response
    {
        return response()
            ->view($view, $data)
            ->header('X-Google-Workspace-Stale', $cache->servedStale() ? '1' : '0')
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Google Workspace API失敗時の共通ログ項目を記録する。
     *
     * @param  string  $message  ログメッセージ
     * @param  Throwable  $exception  捕捉した外部API例外
     * @param  User  $user  操作ユーザー
     * @param  string  $operation  ログ用操作名
     */
    private function logOperationFailure(
        string $message,
        Throwable $exception,
        User $user,
        string $operation,
    ): void {
        Log::warning($message, [
            'exception_class' => $exception::class,
            'operation' => $operation,
            'user_id' => $user->id,
        ]);
    }
}
