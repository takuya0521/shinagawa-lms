<?php

namespace App\Services\GoogleWorkspace\Support;

use App\Exceptions\GoogleWorkspace\GoogleWorkspaceConfigurationException;

/**
 * Google Workspace共通OAuthの設定値を解決する。
 * env参照と必須値検証を一箇所へ集約し、OAuth処理やトークン保存処理が設定構造へ
 * 直接依存しないようにする。
 */
final class GoogleWorkspaceConfiguration
{
    /**
     * OAuth開始前に共通認証情報と要求スコープが揃っているか判定する。
     *
     * @return bool 必須設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        foreach ([
            'client_id',
            'client_secret',
            'authorization_uri',
            'token_uri',
            'revoke_uri',
        ] as $key) {
            if ($this->configuredValue($key) === null) {
                return false;
            }
        }

        return $this->requiredScopes() !== [];
    }

    /**
     * Google Workspace各連携機能に必要なスコープを返す。
     *
     * @return list<string> OAuth認可画面へ要求するスコープ
     */
    public function requiredScopes(): array
    {
        $scopes = config('services.google_workspace.scopes');

        if (! is_array($scopes)) {
            return [];
        }

        return array_values(array_filter(
            $scopes,
            static fn (mixed $scope): bool => is_string($scope) && $scope !== '',
        ));
    }

    /**
     * 要求スコープが設定されていない場合に、認可処理を開始せず明示的に失敗させる。
     *
     * @return list<string> 必須OAuthスコープ
     *
     * @throws GoogleWorkspaceConfigurationException スコープ設定が不足している場合
     */
    public function requiredScopesOrFail(): array
    {
        $scopes = $this->requiredScopes();

        if ($scopes === []) {
            throw new GoogleWorkspaceConfigurationException(
                'Google WorkspaceのOAuthスコープが未設定です。',
            );
        }

        return $scopes;
    }

    /**
     * Google Workspace共通設定値を必須文字列として取得する。
     *
     * @param  string  $key  設定キー
     * @return string 指定キーに対応する必須設定値
     *
     * @throws GoogleWorkspaceConfigurationException 設定値が未設定の場合
     */
    public function requiredValue(string $key): string
    {
        $value = $this->configuredValue($key);

        if ($value === null) {
            throw new GoogleWorkspaceConfigurationException(
                "Google Workspace設定 {$key} が未設定です。",
            );
        }

        return $value;
    }

    /**
     * config経由で設定値を取得し、アプリケーションコードからenvを直接参照しない。
     *
     * @param  string  $key  設定キー
     * @return string|null 指定キーに対応する設定値。未設定時はnull
     */
    public function configuredValue(string $key): ?string
    {
        $value = config("services.google_workspace.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
