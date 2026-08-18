<?php

namespace App\Services\GoogleCalendar\Support;

use App\Exceptions\GoogleCalendar\GoogleCalendarResponseException;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;

/**
 * Google Calendar API設定と取得上限を管理する。
 */
final class GoogleCalendarConfiguration
{
    private const DEFAULT_PAGE_SIZE = 50;

    private const APPLICATION_MAX_PAGE_SIZE = 250;

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
            && $this->configuredValue('api_uri') !== null
            && $this->configuredValue('scope') !== null;
    }

    /**
     * Calendar APIへ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Calendar APIへ要求するOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return [$this->requiredValue('scope')];
    }

    /**
     * Google APIの基底URIを返す。
     *
     * @return string 設定済みのGoogle Calendar API基底URI
     */
    public function apiUri(): string
    {
        return $this->requiredValue('api_uri');
    }

    /**
     * 一覧取得時の最大件数を安全な範囲で返す。
     *
     * @return int 1～250件に補正した予定取得件数
     */
    public function pageSize(): int
    {
        $pageSize = config('services.google_calendar.page_size');

        if (! is_numeric($pageSize)) {
            return self::DEFAULT_PAGE_SIZE;
        }

        return max(1, min(self::APPLICATION_MAX_PAGE_SIZE, (int) $pageSize));
    }

    /**
     * 予定一覧で取得する将来日数を返す。
     *
     * @return int 予定一覧の取得対象とする将来日数
     */
    public function daysAhead(): int
    {
        $days = config('services.google_calendar.days_ahead');

        return is_numeric($days) ? max(1, min(366, (int) $days)) : 180;
    }

    /**
     * 必須設定値を取得し、未設定時は例外を送出する。
     *
     * @param  string  $key  services.google_calendar配下の設定キー
     * @return string 指定キーに対応する必須設定値
     *
     * @throws GoogleCalendarResponseException 必須設定が未設定の場合
     */
    private function requiredValue(string $key): string
    {
        $value = $this->configuredValue($key);

        if ($value === null) {
            throw new GoogleCalendarResponseException("Google Calendar設定 {$key} が未設定です。");
        }

        return $value;
    }

    /**
     * 設定値を文字列として取得する。
     *
     * @param  string  $key  services.google_calendar配下の設定キー
     * @return ?string 指定キーに対応する設定値。未設定時はnull
     */
    private function configuredValue(string $key): ?string
    {
        $value = config("services.google_calendar.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
