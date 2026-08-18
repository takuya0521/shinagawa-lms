<?php

namespace App\Services\GoogleChat\Support;

use App\Exceptions\GoogleChat\GoogleChatResponseException;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;

/**
 * Google Chat APIの設定値とアプリケーション側上限を解決する。
 * 設定値の存在確認や上限補正を各機能Serviceへ分散させず、設定変更時の影響範囲を
 * このクラスへ限定する。
 */
final class GoogleChatConfiguration
{
    private const DEFAULT_SPACE_PAGE_SIZE = 50;

    private const DEFAULT_MESSAGE_PAGE_SIZE = 100;

    private const APPLICATION_MAX_PAGE_SIZE = 1000;

    /**
     * 環境設定の検証と取得上限の補正を各Serviceへ重複させないため、共通設定Serviceを注入する。
     *
     * @param  GoogleWorkspaceService  $workspaceService  Google共通OAuth設定の確認先
     */
    public function __construct(
        private readonly GoogleWorkspaceService $workspaceService,
    ) {}

    /**
     * Google共通OAuthとChat APIの必須設定が揃っているか判定する。
     *
     * @return bool 必須設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->workspaceService->isConfigured()
            && $this->configuredValue('api_uri') !== null
            && $this->configuredValue('upload_uri') !== null
            && $this->configuredValue('spaces_scope') !== null
            && $this->configuredValue('memberships_scope') !== null
            && $this->configuredValue('messages_scope') !== null
            && $this->configuredValue('reactions_scope') !== null
            && $this->configuredValue('delete_scope') !== null;
    }

    /**
     * Chatの作成・編集・削除に必要なOAuthスコープを返す。
     *
     * @return list<string> Chat APIに必要なOAuthスコープ
     *
     * @throws GoogleChatResponseException スコープ設定が不足している場合
     */
    public function requiredScopes(): array
    {
        return [
            $this->requiredValue('spaces_scope'),
            $this->requiredValue('memberships_scope'),
            $this->requiredValue('messages_scope'),
            $this->requiredValue('reactions_scope'),
            $this->requiredValue('delete_scope'),
        ];
    }

    /**
     * Chat APIのベースURLへパスを連結する。
     *
     * @param  string  $path  APIパス
     * @return string Google Chat API URL
     *
     * @throws GoogleChatResponseException API設定が不足している場合
     */
    public function apiUrl(string $path): string
    {
        return rtrim($this->requiredValue('api_uri'), '/').'/'.ltrim($path, '/');
    }

    /**
     * ChatメディアアップロードURLへパスを連結する。
     *
     * @param  string  $path  アップロードAPIパス
     * @return string Google ChatアップロードURL
     *
     * @throws GoogleChatResponseException API設定が不足している場合
     */
    public function uploadUrl(string $path): string
    {
        return rtrim($this->requiredValue('upload_uri'), '/').'/'.ltrim($path, '/');
    }

    /**
     * アプリケーション上限内でスペース取得件数を確定する。
     *
     * @return int 1回のAPI呼び出しで取得する件数
     */
    public function spacePageSize(): int
    {
        return $this->pageSize('space_page_size', self::DEFAULT_SPACE_PAGE_SIZE);
    }

    /**
     * アプリケーション上限内でメッセージ取得件数を確定する。
     *
     * @return int 1回のAPI呼び出しで取得する件数
     */
    public function messagePageSize(): int
    {
        return $this->pageSize('message_page_size', self::DEFAULT_MESSAGE_PAGE_SIZE);
    }

    /**
     * Chat設定値を必須文字列として取得する。
     *
     * @param  string  $key  設定キー
     * @return string 指定キーに対応する必須設定値
     *
     * @throws GoogleChatResponseException 設定値が未設定の場合
     */
    public function requiredValue(string $key): string
    {
        $value = $this->configuredValue($key);

        if ($value === null) {
            throw new GoogleChatResponseException("Google Chat設定 {$key} が未設定です。");
        }

        return $value;
    }

    /**
     * config経由でChat設定値を取得する。
     *
     * @param  string  $key  設定キー
     * @return string|null 指定キーに対応する設定値。未設定時はnull
     */
    public function configuredValue(string $key): ?string
    {
        $value = config("services.google_chat.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * 設定値をGoogle APIとアプリケーション双方の上限内へ補正する。
     *
     * @param  string  $key  件数設定キー
     * @param  int  $default  不正値時の既定件数
     * @return int 補正済み取得件数
     */
    private function pageSize(string $key, int $default): int
    {
        $pageSize = config("services.google_chat.{$key}");

        if (! is_numeric($pageSize)) {
            return $default;
        }

        return max(1, min(self::APPLICATION_MAX_PAGE_SIZE, (int) $pageSize));
    }
}
