<?php

namespace App\Services\GoogleWorkspace\Support;

use App\Exceptions\GoogleWorkspace\GoogleWorkspaceConfigurationException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceResponseException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Google OAuthの認可URL生成とトークン・失効APIへの通信を担当する。
 * DB保存やユーザー判定を含めず、Google OAuthプロトコルとの通信契約だけを扱う。
 */
final class GoogleOAuthClient
{
    private const CONNECT_TIMEOUT_SECONDS = 5;

    private const HTTP_TIMEOUT_SECONDS = 15;

    /**
     * OAuthエンドポイントや認証情報の解決を通信処理へ直書きしないため、共通設定Serviceを注入する。
     *
     * @param  GoogleWorkspaceConfiguration  $configuration  Google共通OAuth設定
     */
    public function __construct(
        private readonly GoogleWorkspaceConfiguration $configuration,
    ) {}

    /**
     * CSRF対策用stateを含むGoogle OAuth認可URLを生成する。
     * 既存権限を維持するincremental authorizationと、更新用トークンを得るofflineアクセスを
     * 明示し、Drive・Classroom・Calendar・Chat・Meet・Formsの追加権限を同一アカウントへ段階的に付与する。
     *
     * @param  string  $state  CSRF対策用のランダム値
     * @param  string  $redirectUri  OAuthコールバックURL
     * @param  string  $loginHint  LMSログインユーザーのメールアドレス
     * @param  list<string>  $scopes  OAuth認可画面へ要求するスコープ
     * @return string Google OAuth認可画面のURL
     */
    public function authorizationUrl(
        string $state,
        string $redirectUri,
        string $loginHint,
        array $scopes,
    ): string {
        $query = http_build_query([
            'access_type' => 'offline',
            'client_id' => $this->configuration->requiredValue('client_id'),
            'include_granted_scopes' => 'true',
            'login_hint' => $loginHint,
            'prompt' => 'consent',
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return $this->configuration->requiredValue('authorization_uri').'?'.$query;
    }

    /**
     * 認可コードをGoogle OAuthトークンへ交換する。
     *
     * @param  string  $authorizationCode  Googleから返された認可コード
     * @param  string  $redirectUri  OAuthコールバックURL
     * @return Response Google OAuthトークンレスポンス
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceConfigurationException OAuth設定が不足している場合
     */
    public function exchangeCode(
        #[\SensitiveParameter] string $authorizationCode,
        string $redirectUri,
    ): Response {
        return $this->request()->post(
            $this->configuration->requiredValue('token_uri'),
            [
                'client_id' => $this->configuration->requiredValue('client_id'),
                'client_secret' => $this->configuration->requiredValue('client_secret'),
                'code' => $authorizationCode,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ],
        )->throw();
    }

    /**
     * リフレッシュトークンを使用してアクセストークンを更新する。
     *
     * @param  string  $refreshToken  暗号化保存から復号された更新用トークン
     * @return Response Google OAuthトークンレスポンス
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceConfigurationException OAuth設定が不足している場合
     */
    public function refresh(#[\SensitiveParameter] string $refreshToken): Response
    {
        return $this->request()->post(
            $this->configuration->requiredValue('token_uri'),
            [
                'client_id' => $this->configuration->requiredValue('client_id'),
                'client_secret' => $this->configuration->requiredValue('client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        )->throw();
    }

    /**
     * Google側のアクセストークンまたはリフレッシュトークンを取り消す。
     *
     * @param  string  $token  取り消し対象トークン
     * @return Response Google OAuth失効レスポンス
     *
     * @throws ConnectionException Googleへ接続できない場合
     * @throws RequestException Google OAuth APIがエラーを返した場合
     * @throws GoogleWorkspaceConfigurationException OAuth設定が不足している場合
     */
    public function revoke(#[\SensitiveParameter] string $token): Response
    {
        return $this->request()->post(
            $this->configuration->requiredValue('revoke_uri'),
            ['token' => $token],
        )->throw();
    }

    /**
     * HTTPレスポンスから必須文字列を取得し、不完全な認証状態を保存させない。
     *
     * @param  Response  $response  Google OAuthレスポンス
     * @param  string  $key  JSONキー
     * @return string 指定した必須項目の文字列値
     *
     * @throws GoogleWorkspaceResponseException 必須文字列が存在しない場合
     */
    public function stringValue(Response $response, string $key): string
    {
        $value = $response->json($key);

        if (! is_string($value) || $value === '') {
            throw new GoogleWorkspaceResponseException(
                "Google OAuth応答に{$key}がありません。",
            );
        }

        return $value;
    }

    /**
     * HTTPレスポンスから有効期限などの必須整数を取得する。
     *
     * @param  Response  $response  Google OAuthレスポンス
     * @param  string  $key  JSONキー
     * @return int 指定した必須項目の整数値
     *
     * @throws GoogleWorkspaceResponseException 必須整数が存在しない場合
     */
    public function integerValue(Response $response, string $key): int
    {
        $value = $response->json($key);

        if (! is_numeric($value)) {
            throw new GoogleWorkspaceResponseException(
                "Google OAuth応答に{$key}がありません。",
            );
        }

        return (int) $value;
    }

    /**
     * GoogleのトークンAPIへフォーム形式で接続するHTTPクライアントを返す。
     *
     * @return PendingRequest Google OAuthトークンAPI向けHTTPクライアント
     */
    private function request(): PendingRequest
    {
        return Http::asForm()
            ->acceptJson()
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::HTTP_TIMEOUT_SECONDS);
    }
}
