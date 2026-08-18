<?php

namespace App\Services\GoogleWorkspace;

use App\Exceptions\GoogleWorkspace\GoogleWorkspaceConfigurationException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceReauthenticationRequiredException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceResponseException;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use App\Services\GoogleWorkspace\Support\GoogleOAuthClient;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceConfiguration;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceScopeService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Google Workspace各APIで共有するOAuth連携情報の保存とトークン更新を統括する。
 * OAuthプロトコル通信、設定解決、スコープ比較を専用クラスへ分離し、このServiceは
 * LMSユーザーと暗号化済み接続レコードのライフサイクルだけを担当する。
 */
final class GoogleWorkspaceService
{
    private const TOKEN_EXPIRY_MARGIN_SECONDS = 60;

    /**
     * OAuth接続情報の保存とトークン更新をプロトコル通信から分離するため、専用依存を注入する。
     *
     * @param  GoogleWorkspaceConfiguration  $configuration  Google共通OAuth設定
     * @param  GoogleWorkspaceScopeService  $scopeService  OAuthスコープの比較担当
     * @param  GoogleOAuthClient  $oauthClient  Google OAuth通信担当
     * @param  GoogleWorkspaceCache  $cache  Google API表示データのSWRキャッシュ
     */
    public function __construct(
        private readonly GoogleWorkspaceConfiguration $configuration,
        private readonly GoogleWorkspaceScopeService $scopeService,
        private readonly GoogleOAuthClient $oauthClient,
        private readonly GoogleWorkspaceCache $cache,
    ) {}

    /**
     * OAuth開始前に共通認証情報と要求スコープが揃っているか判定する。
     *
     * @return bool 必須設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->configuration->isConfigured();
    }

    /**
     * Google Workspace各連携機能に必要なスコープを返す。
     *
     * @return list<string> OAuth認可画面へ要求するスコープ
     */
    public function requiredScopes(): array
    {
        return $this->configuration->requiredScopes();
    }

    /**
     * CSRF対策用stateを含むGoogle OAuth認可URLを生成する。
     *
     * @param  string  $state  CSRF対策用のランダム値
     * @param  string  $redirectUri  OAuthコールバックURL
     * @param  string  $loginHint  LMSログインユーザーのメールアドレス
     * @return string Google OAuth認可画面のURL
     *
     * @throws GoogleWorkspaceConfigurationException 必須設定が不足している場合
     */
    public function authorizationUrl(
        string $state,
        string $redirectUri,
        string $loginHint,
    ): string {
        return $this->oauthClient->authorizationUrl(
            $state,
            $redirectUri,
            $loginHint,
            $this->configuration->requiredScopesOrFail(),
        );
    }

    /**
     * 認可コードを共通OAuthトークンへ交換し、ユーザー単位で暗号化保存する。
     * Googleが再認可時にリフレッシュトークンを返さない場合は既存値を保持し、
     * 連携済み環境で自動更新が途切れないようにする。
     *
     * @param  User  $user  連携対象ユーザー
     * @param  string  $authorizationCode  Googleから返された認可コード
     * @param  string  $redirectUri  OAuthコールバックURL
     * @return GoogleDriveConnection 保存した共通連携情報
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException GoogleのトークンAPIがエラーを返した場合
     * @throws GoogleWorkspaceConfigurationException 必須設定が不足している場合
     * @throws GoogleWorkspaceResponseException Googleの応答が必須契約を満たさない場合
     */
    public function connect(
        User $user,
        #[\SensitiveParameter] string $authorizationCode,
        string $redirectUri,
    ): GoogleDriveConnection {
        $response = $this->oauthClient->exchangeCode($authorizationCode, $redirectUri);
        $requiredScopes = $this->configuration->requiredScopesOrFail();
        $accessToken = $this->oauthClient->stringValue($response, 'access_token');
        $refreshToken = $response->json('refresh_token');
        $expiresIn = $this->oauthClient->integerValue($response, 'expires_in');
        $grantedScope = $response->json('scope');
        $scope = is_string($grantedScope) && $grantedScope !== ''
            ? $grantedScope
            : implode(' ', $requiredScopes);

        // Google OAuthは部分同意などにより要求スコープの一部だけを返す場合がある。
        // 実際に付与されたscopeを保存し、各API利用時に必要権限だけを判定する。

        if (! is_string($refreshToken) || $refreshToken === '') {
            $refreshToken = $user->googleDriveConnection?->refresh_token;
        }

        $connection = GoogleDriveConnection::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => now()->addSeconds(
                    $this->effectiveTokenLifetime($expiresIn),
                ),
                'scope' => $scope,
                'last_connected_at' => now(),
            ],
        );

        // 再連携前の権限・API結果を画面へ残さないため、保存成功後に全サービスを無効化する。
        $this->cache->invalidateAll($user);

        return $connection;
    }

    /**
     * 指定APIに必要な権限を確認し、利用可能なアクセストークンを返す。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  list<string>  $requiredScopes  呼び出すAPIに必要なスコープ
     * @return string API呼び出しに使用するアクセストークン
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceConfigurationException OAuth設定が不足している場合
     * @throws GoogleWorkspaceReauthenticationRequiredException 連携または追加権限が必要な場合
     * @throws GoogleWorkspaceResponseException Googleの応答が必須契約を満たさない場合
     */
    public function accessToken(User $user, array $requiredScopes): string
    {
        $connection = $user->googleDriveConnection;

        if (! $connection instanceof GoogleDriveConnection) {
            throw new GoogleWorkspaceReauthenticationRequiredException(
                'Google Workspaceとの連携が必要です。',
            );
        }

        if (! $this->hasScopes($connection, $requiredScopes)) {
            throw new GoogleWorkspaceReauthenticationRequiredException(
                'Google Workspaceの追加権限が必要です。',
            );
        }

        if ($connection->token_expires_at === null || $connection->token_expires_at->isFuture()) {
            return $connection->access_token;
        }

        return $this->refreshAccessToken($connection);
    }

    /**
     * APIから401が返った場合に限り、アクセストークンを強制更新して返す。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  list<string>  $requiredScopes  呼び出すAPIに必要なスコープ
     * @return string 更新後のアクセストークン
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceConfigurationException OAuth設定が不足している場合
     * @throws GoogleWorkspaceReauthenticationRequiredException 連携または追加権限が必要な場合
     * @throws GoogleWorkspaceResponseException Googleの応答が必須契約を満たさない場合
     */
    public function forceRefreshAccessToken(User $user, array $requiredScopes): string
    {
        $connection = $user->googleDriveConnection;

        if (! $connection instanceof GoogleDriveConnection
            || ! $this->hasScopes($connection, $requiredScopes)) {
            throw new GoogleWorkspaceReauthenticationRequiredException(
                'Google Workspaceの再連携が必要です。',
            );
        }

        return $this->refreshAccessToken($connection);
    }

    /**
     * 保存済みスコープに、指定APIが必要とする権限がすべて含まれるか判定する。
     *
     * @param  GoogleDriveConnection  $connection  保存済みGoogle OAuth連携情報
     * @param  list<string>  $requiredScopes  判定対象スコープ
     * @return bool 必要な権限がすべて含まれる場合はtrue
     */
    public function hasScopes(GoogleDriveConnection $connection, array $requiredScopes): bool
    {
        return $this->scopeService->hasScopes($connection, $requiredScopes);
    }

    /**
     * Google側のトークン取り消しを試みた後、LMS側の連携情報を必ず削除する。
     * Google側が一時的に応答しない場合でも利用者の解除意思を優先し、LMS内へ
     * 暗号化トークンを残し続けない。取り消し失敗は機密値を含めずログへ残す。
     *
     * @param  User  $user  解除対象ユーザー
     */
    public function disconnect(User $user): void
    {
        $connection = $user->googleDriveConnection;

        if ($connection instanceof GoogleDriveConnection) {
            $this->revokeRemoteToken($connection, $user);
        }

        $user->googleDriveConnection()->delete();
        $user->unsetRelation('googleDriveConnection');
        $this->cache->invalidateAll($user);
    }

    /**
     * リフレッシュトークンを使用してアクセストークンを更新し、暗号化保存する。
     *
     * @param  GoogleDriveConnection  $connection  Google Workspace共通連携情報
     * @return string 更新後のアクセストークン
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceConfigurationException OAuth設定が不足している場合
     * @throws GoogleWorkspaceReauthenticationRequiredException 更新用トークンがない場合
     * @throws GoogleWorkspaceResponseException Googleの応答が必須契約を満たさない場合
     */
    private function refreshAccessToken(GoogleDriveConnection $connection): string
    {
        if ($connection->refresh_token === null) {
            throw new GoogleWorkspaceReauthenticationRequiredException(
                'Google Workspaceの再連携が必要です。',
            );
        }

        $response = $this->oauthClient->refresh($connection->refresh_token);
        $accessToken = $this->oauthClient->stringValue($response, 'access_token');
        $expiresIn = $this->oauthClient->integerValue($response, 'expires_in');

        $connection->forceFill([
            'access_token' => $accessToken,
            'token_expires_at' => now()->addSeconds(
                $this->effectiveTokenLifetime($expiresIn),
            ),
        ])->save();

        return $accessToken;
    }

    /**
     * API実行中の期限切れを避けるため、有効期限から安全余裕を差し引く。
     *
     * @param  int  $expiresIn  Googleが返した有効秒数
     * @return int LMSで有効とみなす秒数
     */
    private function effectiveTokenLifetime(int $expiresIn): int
    {
        return max(1, $expiresIn - self::TOKEN_EXPIRY_MARGIN_SECONDS);
    }

    /**
     * Google側のトークン取り消しを試み、失敗時は機密値を除いた情報だけを記録する。
     *
     * @param  GoogleDriveConnection  $connection  Google Workspace共通連携情報
     * @param  User  $user  解除対象ユーザー
     */
    private function revokeRemoteToken(GoogleDriveConnection $connection, User $user): void
    {
        $token = $connection->refresh_token ?? $connection->access_token;

        try {
            $this->oauthClient->revoke($token);
        } catch (ConnectionException|RequestException|GoogleWorkspaceConfigurationException $exception) {
            Log::warning('Google Workspace OAuthトークンを取り消せませんでした。', [
                'exception_class' => $exception::class,
                'operation' => 'google_workspace_disconnect',
                'user_id' => $user->id,
            ]);
        }
    }
}
