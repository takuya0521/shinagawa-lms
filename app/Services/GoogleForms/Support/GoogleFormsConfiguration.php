<?php

namespace App\Services\GoogleForms\Support;

use App\Exceptions\GoogleForms\GoogleFormsResponseException;
use App\Services\GoogleDrive\Support\GoogleDriveConfiguration;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;

/**
 * Google Forms APIの設定値と取得上限を解決する。
 */
final class GoogleFormsConfiguration
{
    private const DEFAULT_PAGE_SIZE = 50;

    private const APPLICATION_MAX_PAGE_SIZE = 100;

    /**
     * @param  GoogleWorkspaceService  $workspaceService  Google共通OAuth設定の確認先
     * @param  GoogleDriveConfiguration  $driveConfiguration  Forms一覧取得に使用するDrive設定
     */
    public function __construct(
        private readonly GoogleWorkspaceService $workspaceService,
        private readonly GoogleDriveConfiguration $driveConfiguration,
    ) {}

    /**
     * Google共通OAuth・Drive・Forms APIの必須設定が揃っているか判定する。
     *
     * @return bool 必須設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->workspaceService->isConfigured()
            && $this->driveConfiguration->isConfigured()
            && $this->configuredValue('api_uri') !== null;
    }

    /**
     * Formsの参照・更新・回答取得に利用するOAuthスコープ一覧を返す。
     *
     * @return list<string> Forms APIへ渡すOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return $this->driveConfiguration->requiredScopes();
    }

    /**
     * Forms APIのベースURLへパスを連結する。
     *
     * @param  string  $path  APIパス
     * @return string Google Forms API URL
     */
    public function apiUrl(string $path): string
    {
        return rtrim($this->requiredValue('api_uri'), '/').'/'.ltrim($path, '/');
    }

    /**
     * Google Driveファイル一覧APIのURLを返す。
     *
     * @return string Google Drive files API URL
     */
    public function driveFilesUrl(): string
    {
        return $this->driveConfiguration->filesUri();
    }

    /**
     * フォーム一覧取得件数をアプリケーション上限内へ補正する。
     *
     * @return int 1～100件に補正した取得件数
     */
    public function pageSize(): int
    {
        $pageSize = config('services.google_forms.page_size');

        if (! is_numeric($pageSize)) {
            return self::DEFAULT_PAGE_SIZE;
        }

        return max(1, min(self::APPLICATION_MAX_PAGE_SIZE, (int) $pageSize));
    }

    /**
     * Forms設定値を必須文字列として取得する。
     *
     * @param  string  $key  設定キー
     * @return string 設定済み値
     *
     * @throws GoogleFormsResponseException 設定値が未設定の場合
     */
    private function requiredValue(string $key): string
    {
        $value = $this->configuredValue($key);

        if ($value === null) {
            throw new GoogleFormsResponseException("Google Forms設定 {$key} が未設定です。");
        }

        return $value;
    }

    /**
     * config経由でForms設定値を取得する。
     *
     * @param  string  $key  設定キー
     * @return string|null 指定キーに対応する設定値。未設定時はnull
     */
    private function configuredValue(string $key): ?string
    {
        $value = config("services.google_forms.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
