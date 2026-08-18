<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Google Workspace共通OAuthの開始・コールバック・解除を担当する。
 */
final class OAuthController extends GoogleWorkspaceController
{
    private const SESSION_STATE_KEY = 'google_workspace_oauth_state';

    /**
     * Google OAuth認可画面へ遷移する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  GoogleWorkspaceService  $service  共通OAuth処理
     * @return RedirectResponse Google認可画面へのリダイレクト
     */
    public function connect(Request $request, GoogleWorkspaceService $service): RedirectResponse
    {
        abort_unless($service->isConfigured(), 503);

        $user = $this->resolveUser($request);
        $state = Str::random(64);
        $request->session()->put(self::SESSION_STATE_KEY, $state);

        return redirect()->away($service->authorizationUrl(
            $state,
            route('student.google-drive.callback'),
            $user->email,
        ));
    }

    /**
     * Googleの認可結果を検証し、共通トークンを暗号化保存する。
     *
     * @param  Request  $request  OAuthコールバック
     * @param  GoogleWorkspaceService  $service  共通OAuth処理
     * @return RedirectResponse Google Drive画面へのリダイレクト
     */
    public function callback(Request $request, GoogleWorkspaceService $service): RedirectResponse
    {
        $user = $this->resolveUser($request);
        $expectedState = $request->session()->pull(self::SESSION_STATE_KEY);
        $actualState = $request->string('state')->toString();

        abort_unless(
            is_string($expectedState)
                && $actualState !== ''
                && hash_equals($expectedState, $actualState),
            403,
        );

        if ($request->filled('error')) {
            return redirect()
                ->route('google-workspace.drive.index')
                ->with('error', 'Google Workspaceとの連携がキャンセルされました。');
        }

        $authorizationCode = $request->string('code')->toString();
        if ($authorizationCode === '') {
            return redirect()
                ->route('google-workspace.drive.index')
                ->with('error', 'Googleから認可コードを取得できませんでした。');
        }

        try {
            $service->connect(
                $user,
                $authorizationCode,
                route('student.google-drive.callback'),
            );
        } catch (ConnectionException|RequestException|GoogleWorkspaceException $exception) {
            Log::warning('Google Workspace OAuth連携に失敗しました。', [
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'operation' => 'google_workspace_oauth_callback',
                'user_id' => $user->id,
            ]);

            return redirect()
                ->route('google-workspace.drive.index')
                ->with('error', 'Google Workspaceとの連携に失敗しました。設定を確認して、もう一度お試しください。');
        }

        return redirect()
            ->route('google-workspace.drive.index')
            ->with('success', 'Google Workspaceの操作権限で連携しました。');
    }

    /**
     * Google側のトークン取り消しとLMS側連携情報削除を実行する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  GoogleWorkspaceService  $service  共通OAuth処理
     * @return RedirectResponse Google Drive画面へのリダイレクト
     */
    public function disconnect(Request $request, GoogleWorkspaceService $service): RedirectResponse
    {
        $service->disconnect($this->resolveUser($request));

        return redirect()
            ->route('google-workspace.drive.index')
            ->with('success', 'Google Workspaceとの連携を解除しました。');
    }
}
