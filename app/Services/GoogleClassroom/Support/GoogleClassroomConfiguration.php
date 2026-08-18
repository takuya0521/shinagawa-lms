<?php

namespace App\Services\GoogleClassroom\Support;

use App\Exceptions\GoogleClassroom\GoogleClassroomResponseException;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;

/**
 * Google Classroom APIの接続先、OAuthスコープ、取得件数を解決する。
 */
final class GoogleClassroomConfiguration
{
    private const DEFAULT_PAGE_SIZE = 50;

    private const APPLICATION_MAX_PAGE_SIZE = 100;

    /**
     * @param  GoogleWorkspaceService  $workspaceService  Google共通OAuth設定の確認先
     */
    public function __construct(
        private readonly GoogleWorkspaceService $workspaceService,
    ) {}

    /**
     * Google共通OAuthとClassroom APIの必須設定が揃っているか判定する。
     *
     * @return bool 必須設定が揃っている場合はtrue
     */
    public function isConfigured(): bool
    {
        return $this->workspaceService->isConfigured()
            && $this->configuredValue('api_uri') !== null
            && $this->requiredScopes() !== [];
    }

    /**
     * Classroomのクラス・課題・お知らせ参照に必要なスコープを返す。
     *
     * @return list<string> Classroom APIへ要求するOAuthスコープ
     */
    public function requiredScopes(): array
    {
        $scopes = config('services.google_classroom.scopes');

        if (! is_array($scopes)) {
            return [];
        }

        return array_values(array_filter(
            $scopes,
            static fn (mixed $scope): bool => is_string($scope) && $scope !== '',
        ));
    }

    /**
     * Classroom APIのベースURLへパスを連結する。
     *
     * @param  string  $path  APIパス
     * @return string Google Classroom API URL
     */
    public function apiUrl(string $path): string
    {
        return rtrim($this->requiredValue('api_uri'), '/').'/'.ltrim($path, '/');
    }

    /**
     * 一度に取得する件数をGoogle API上限内へ補正する。
     *
     * @return int 1～100件に補正した取得件数
     */
    public function pageSize(): int
    {
        $pageSize = config('services.google_classroom.page_size');

        if (! is_numeric($pageSize)) {
            return self::DEFAULT_PAGE_SIZE;
        }

        return max(1, min(self::APPLICATION_MAX_PAGE_SIZE, (int) $pageSize));
    }

    /**
     * Classroom設定値を必須文字列として取得する。
     *
     * @param  string  $key  設定キー
     * @return string 設定済み値
     *
     * @throws GoogleClassroomResponseException 設定値が未設定の場合
     */
    private function requiredValue(string $key): string
    {
        $value = $this->configuredValue($key);

        if ($value === null) {
            throw new GoogleClassroomResponseException(
                "Google Classroom設定 {$key} が未設定です。",
            );
        }

        return $value;
    }

    /**
     * config経由でClassroom設定値を取得する。
     *
     * @param  string  $key  設定キー
     * @return string|null 指定キーに対応する設定値。未設定時はnull
     */
    private function configuredValue(string $key): ?string
    {
        $value = config("services.google_classroom.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
