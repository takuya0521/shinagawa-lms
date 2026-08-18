<?php

namespace App\Services\GoogleWorkspace\Support;

use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleWorkspace\Concerns\NormalizesGoogleQueryParameters;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Workspace各APIへ共通の認証・再試行条件で接続するHTTPクライアント。
 * 各サービスへ401時のトークン更新処理を重複させず、無制限再試行を防ぐため、
 * アクセストークン更新後の再送は1回だけに限定する。
 */
final class GoogleApiClient
{
    use NormalizesGoogleQueryParameters;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * 401時のトークン更新と再送条件を各機能Serviceへ重複させないため、共通OAuth Serviceを注入する。
     *
     * @param  GoogleWorkspaceService  $workspaceService  Google Workspace接続情報とトークン更新を管理するサービス
     * @param  GoogleWorkspaceCacheInvalidator  $cacheInvalidator  更新成功後の画面キャッシュ無効化を統括するサービス
     */
    public function __construct(
        private readonly GoogleWorkspaceService $workspaceService,
        private readonly GoogleWorkspaceCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * 必要スコープを確認してGoogle APIを呼び出す。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  list<string>  $requiredScopes  呼び出すAPIに必要なOAuthスコープ
     * @param  string  $method  HTTPメソッド
     * @param  string  $url  Google API URL
     * @param  array<string, mixed>  $options  Laravel HTTPクライアントへ渡すオプション
     * @param  bool  $acceptJson  JSON応答を要求する場合はtrue
     * @param  int  $timeoutSeconds  API応答待機秒数
     * @return Response Google APIレスポンス
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function send(
        User $user,
        array $requiredScopes,
        string $method,
        string $url,
        array $options = [],
        bool $acceptJson = true,
        int $timeoutSeconds = 30,
    ): Response {
        $options = $this->normalizeGoogleQueryParameters($options);
        $accessToken = $this->workspaceService->accessToken($user, $requiredScopes);
        $response = $this->request($accessToken, $acceptJson, $timeoutSeconds)
            ->send($method, $url, $options);

        // Google側でトークンだけが失効した場合に限り、更新後の再送を1回だけ許可する。
        if ($response->status() === 401) {
            $accessToken = $this->workspaceService->forceRefreshAccessToken($user, $requiredScopes);
            $response = $this->request($accessToken, $acceptJson, $timeoutSeconds)
                ->send($method, $url, $options);
        }

        $response->throw();
        $this->cacheInvalidator->invalidateAfterMutation($user, $method, $url);

        return $response;
    }

    /**
     * 相互に依存しないGoogle APIリクエストを同時送信し、画面初期表示の待ち時間を短縮する。
     * いずれかが401を返した場合はアクセストークンを1回だけ更新し、同じ一式を再送する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  list<string>  $requiredScopes  呼び出すAPIに必要なOAuthスコープ
     * @param  array  $requests  識別名をキーにしたAPIリクエスト定義
     *
     * @phpstan-param array<string, array{
     *     method: string,
     *     url: string,
     *     options?: array<string, mixed>,
     *     accept_json?: bool,
     *     timeout_seconds?: int
     * }> $requests
     *
     * @return array<string, Response> 識別名を維持したGoogle APIレスポンス
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function sendMany(User $user, array $requiredScopes, array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $accessToken = $this->workspaceService->accessToken($user, $requiredScopes);
        $responses = $this->pool($accessToken, $requests);

        if ($this->containsUnauthorizedResponse($responses)) {
            $accessToken = $this->workspaceService->forceRefreshAccessToken($user, $requiredScopes);
            $responses = $this->pool($accessToken, $requests);
        }

        foreach ($responses as $name => $response) {
            $response->throw();
            $request = $requests[$name] ?? null;

            if (is_array($request)) {
                $this->cacheInvalidator->invalidateAfterMutation(
                    $user,
                    $request['method'],
                    $request['url'],
                );
            }
        }

        return $responses;
    }

    /**
     * ストリーム送信など通常のsendでは組み立てにくい処理向けにアクセストークンを返す。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  list<string>  $requiredScopes  呼び出すAPIに必要なOAuthスコープ
     * @return string API呼び出しに使用するアクセストークン
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function accessToken(User $user, array $requiredScopes): string
    {
        return $this->workspaceService->accessToken($user, $requiredScopes);
    }

    /**
     * ストリーム再送時に使用する更新済みアクセストークンを返す。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  list<string>  $requiredScopes  呼び出すAPIに必要なOAuthスコープ
     * @return string 更新後のアクセストークン
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     */
    public function refreshAccessToken(User $user, array $requiredScopes): string
    {
        return $this->workspaceService->forceRefreshAccessToken($user, $requiredScopes);
    }

    /**
     * Google APIリクエスト定義をLaravel HTTP Poolへ登録し、同時実行する。
     *
     * @param  string  $accessToken  Google APIアクセストークン
     * @param  array  $requests  識別名をキーにしたAPIリクエスト定義
     *
     * @phpstan-param array<string, array{
     *     method: string,
     *     url: string,
     *     options?: array<string, mixed>,
     *     accept_json?: bool,
     *     timeout_seconds?: int
     * }> $requests
     *
     * @return array<string, Response> 識別名を維持したGoogle APIレスポンス
     *
     * @throws ConnectionException Google APIの並列送信に失敗した場合
     */
    private function pool(#[\SensitiveParameter] string $accessToken, array $requests): array
    {
        $results = Http::pool(function (Pool $pool) use ($accessToken, $requests): void {
            foreach ($requests as $name => $request) {
                $pending = $pool->as($name)
                    ->withToken($accessToken)
                    ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                    ->timeout(max(1, (int) ($request['timeout_seconds'] ?? 30)));

                if (($request['accept_json'] ?? true) === true) {
                    $pending = $pending->acceptJson();
                }

                $pending->send(
                    $request['method'],
                    $request['url'],
                    $this->normalizeGoogleQueryParameters($request['options'] ?? []),
                );
            }
        });

        $responses = [];

        foreach ($results as $name => $result) {
            if ($result instanceof ConnectionException) {
                throw $result;
            }

            if ($result instanceof Throwable) {
                throw new ConnectionException(
                    'Google APIの並列通信に失敗しました。',
                    previous: $result,
                );
            }

            $responses[(string) $name] = $result;
        }

        return $responses;
    }

    /**
     * 並列応答の中にアクセストークン失効を示す401が含まれるか判定する。
     *
     * @param  array<string, Response>  $responses  Google APIレスポンス一覧
     * @return bool 401応答を含む場合はtrue
     */
    private function containsUnauthorizedResponse(array $responses): bool
    {
        foreach ($responses as $response) {
            if ($response->status() === 401) {
                return true;
            }
        }

        return false;
    }

    /**
     * Google API向けのBearerトークン付きHTTPクライアントを生成する。
     *
     * @param  string  $accessToken  Google APIアクセストークン
     * @param  bool  $acceptJson  JSON応答を要求する場合はtrue
     * @param  int  $timeoutSeconds  API通信のタイムアウト秒数
     * @return PendingRequest 共通設定を適用したHTTPクライアント
     */
    public function request(
        #[\SensitiveParameter] string $accessToken,
        bool $acceptJson = true,
        int $timeoutSeconds = 30,
    ): PendingRequest {
        $request = Http::withToken($accessToken)
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout($timeoutSeconds);

        return $acceptJson ? $request->acceptJson() : $request;
    }
}
