<?php

namespace App\Services\GoogleMeet\Support;

use App\Exceptions\GoogleMeet\GoogleMeetResponseException;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;

/**
 * Google Meet REST APIの設定値と取得上限を解決する。
 */
final class GoogleMeetConfiguration
{
    private const DEFAULT_PAGE_SIZE = 25;

    private const APPLICATION_MAX_PAGE_SIZE = 100;

    /**
     * @param  GoogleWorkspaceService  $workspaceService  Google共通OAuth設定の確認先
     */
    public function __construct(
        private readonly GoogleWorkspaceService $workspaceService,
    ) {}

    /**
     * Google共通OAuthとMeet APIの必須設定が揃っているか判定する。
     *
     * @return bool 必須設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->workspaceService->isConfigured()
            && $this->configuredValue('api_uri') !== null
            && $this->configuredValue('create_scope') !== null
            && $this->configuredValue('read_scope') !== null;
    }

    /**
     * Meet APIへ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Meet APIに必要なOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return [
            $this->requiredValue('create_scope'),
            $this->requiredValue('read_scope'),
        ];
    }

    /**
     * Meet APIのベースURLへパスを連結する。
     *
     * @param  string  $path  APIパス
     * @return string Google Meet API URL
     */
    public function apiUrl(string $path): string
    {
        return rtrim($this->requiredValue('api_uri'), '/').'/'.ltrim($path, '/');
    }

    /**
     * 会議履歴の取得件数をGoogle API上限内へ補正する。
     *
     * @return int 1～100件に補正した取得件数
     */
    public function pageSize(): int
    {
        $pageSize = config('services.google_meet.page_size');

        if (! is_numeric($pageSize)) {
            return self::DEFAULT_PAGE_SIZE;
        }

        return max(1, min(self::APPLICATION_MAX_PAGE_SIZE, (int) $pageSize));
    }

    /**
     * Meet設定値を必須文字列として取得する。
     *
     * @param  string  $key  設定キー
     * @return string 設定済み値
     *
     * @throws GoogleMeetResponseException 設定値が未設定の場合
     */
    private function requiredValue(string $key): string
    {
        $value = $this->configuredValue($key);

        if ($value === null) {
            throw new GoogleMeetResponseException("Google Meet設定 {$key} が未設定です。");
        }

        return $value;
    }

    /**
     * config経由でMeet設定値を取得する。
     *
     * @param  string  $key  設定キー
     * @return string|null 指定キーに対応する設定値。未設定時はnull
     */
    private function configuredValue(string $key): ?string
    {
        $value = config("services.google_meet.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
