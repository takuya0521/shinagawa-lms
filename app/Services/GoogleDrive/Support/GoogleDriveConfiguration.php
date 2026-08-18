<?php

namespace App\Services\GoogleDrive\Support;

use App\Exceptions\GoogleDrive\GoogleDriveConfigurationException;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;

/**
 * Google Drive API設定の取得とアプリケーション上限の適用を担当する。
 */
final class GoogleDriveConfiguration
{
    private const DEFAULT_PAGE_SIZE = 30;

    private const APPLICATION_MAX_PAGE_SIZE = 100;

    /**
     * 環境設定の検証と取得上限の補正を各Serviceへ重複させないため、共通設定Serviceを注入する。
     *
     * @param  GoogleWorkspaceService  $workspaceService  Google Workspace接続情報とトークン更新を管理するサービス
     */
    public function __construct(
        private readonly GoogleWorkspaceService $workspaceService,
    ) {}

    /**
     * Google API連携に必要な設定が揃っているか判定する。
     *
     * @return bool Google API利用に必要な設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->workspaceService->isConfigured()
            && $this->configuredValue('files_uri') !== null
            && $this->configuredValue('upload_uri') !== null
            && $this->configuredValue('drives_uri') !== null
            && $this->configuredValue('scope') !== null;
    }

    /**
     * Drive APIへ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Drive APIへ要求するOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return [$this->requiredValue('scope')];
    }

    /**
     * Google DriveファイルAPIの基底URIを返す。
     *
     * @return string 設定済みのGoogle DriveファイルAPI基底URI
     */
    public function filesUri(): string
    {
        return $this->requiredValue('files_uri');
    }

    /**
     * Google DriveアップロードAPIの基底URIを返す。
     *
     * @return string 設定済みのGoogle DriveアップロードAPI基底URI
     */
    public function uploadUri(): string
    {
        return $this->requiredValue('upload_uri');
    }

    /**
     * 共有ドライブAPIの基底URIを返す。
     *
     * @return string 設定済みの共有ドライブAPI基底URI
     */
    public function drivesUri(): string
    {
        return $this->requiredValue('drives_uri');
    }

    /**
     * 一覧取得時の最大件数を安全な範囲で返す。
     *
     * @return int 1～100件に補正した一覧取得件数
     */
    public function pageSize(): int
    {
        $pageSize = config('services.google_drive.page_size');

        if (! is_numeric($pageSize)) {
            return self::DEFAULT_PAGE_SIZE;
        }

        return max(1, min(self::APPLICATION_MAX_PAGE_SIZE, (int) $pageSize));
    }

    /**
     * 必須設定値を取得し、未設定時は例外を送出する。
     *
     * @param  string  $key  services.google_drive配下の設定キー
     * @return string 指定キーに対応する必須設定値
     *
     * @throws GoogleDriveConfigurationException 必須設定が未設定の場合
     */
    private function requiredValue(string $key): string
    {
        $value = $this->configuredValue($key);

        if ($value === null) {
            throw new GoogleDriveConfigurationException("Google Drive設定 {$key} が未設定です。");
        }

        return $value;
    }

    /**
     * 設定値を文字列として取得する。
     *
     * @param  string  $key  services.google_drive配下の設定キー
     * @return ?string 指定キーに対応する設定値。未設定時はnull
     */
    private function configuredValue(string $key): ?string
    {
        $value = config("services.google_drive.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
