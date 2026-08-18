<?php

namespace App\Services\GoogleMeet;

use App\Data\GoogleMeetConference;
use App\Data\GoogleMeetSpace;
use App\Exceptions\GoogleMeet\GoogleMeetResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleMeet\Support\GoogleMeetConfiguration;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/**
 * Google Meet REST APIの会議スペース作成・参照と会議履歴取得を担当する。
 */
final class GoogleMeetService
{
    /**
     * @param  GoogleMeetConfiguration  $configuration  Meet API設定
     * @param  GoogleApiClient  $client  Google共通HTTPクライアント
     */
    public function __construct(
        private readonly GoogleMeetConfiguration $configuration,
        private readonly GoogleApiClient $client,
    ) {}

    /**
     * Google Meet APIへ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Meet APIに必要なOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return $this->configuration->requiredScopes();
    }

    /**
     * 新しいGoogle Meet会議スペースを作成する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @return GoogleMeetSpace 作成した会議スペース
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleMeetResponseException Meet API応答が不正な場合
     */
    public function createSpace(User $user): GoogleMeetSpace
    {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'POST',
            $this->configuration->apiUrl('spaces'),
            ['json' => new \stdClass],
        );

        return $this->mapSpace($response->json());
    }

    /**
     * 会議コードまたはスペース名からGoogle Meet会議スペースを取得する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  string  $meetingCodeOrName  会議コードまたはspaces/から始まるリソース名
     * @return GoogleMeetSpace 会議スペース
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleMeetResponseException Meet API応答が不正な場合
     */
    public function space(User $user, string $meetingCodeOrName): GoogleMeetSpace
    {
        $name = str_starts_with($meetingCodeOrName, 'spaces/')
            ? $meetingCodeOrName
            : 'spaces/'.$meetingCodeOrName;
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->apiUrl($name),
        );

        return $this->mapSpace($response->json());
    }

    /**
     * Meet会議履歴を開始日時の新しい順で取得する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  ?string  $meetingCode  指定した会議コードだけへ絞り込む場合の値
     * @return Collection<int, GoogleMeetConference> 会議履歴
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleMeetResponseException Meet API応答が不正な場合
     */
    public function conferences(User $user, ?string $meetingCode = null): Collection
    {
        $query = [
            'pageSize' => $this->configuration->pageSize(),
            'fields' => 'conferenceRecords(name,startTime,endTime,expireTime,space)',
        ];

        if ($meetingCode !== null && $meetingCode !== '') {
            $query['filter'] = sprintf('space.meeting_code = "%s"', $meetingCode);
        }

        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->apiUrl('conferenceRecords'),
            ['query' => $query],
        );
        $items = $response->json('conferenceRecords', []);

        if (! is_array($items)) {
            throw new GoogleMeetResponseException('Google Meetの会議履歴を読み取れませんでした。');
        }

        return collect($items)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): GoogleMeetConference => $this->mapConference($item))
            ->values();
    }

    /**
     * Meet APIのスペース応答を画面表示用DTOへ変換する。
     *
     * @param  mixed  $payload  Google Meet API応答
     * @return GoogleMeetSpace 変換済みスペース情報
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleMeetResponseException 必須項目が不足している場合
     */
    private function mapSpace(mixed $payload): GoogleMeetSpace
    {
        if (! is_array($payload)) {
            throw new GoogleMeetResponseException('Google Meetの会議スペースを読み取れませんでした。');
        }

        $name = $payload['name'] ?? null;
        $meetingCode = $payload['meetingCode'] ?? null;
        $meetingUri = $payload['meetingUri'] ?? null;
        $activeConference = $payload['activeConference']['conferenceRecord'] ?? null;

        if (! is_string($name) || $name === ''
            || ! is_string($meetingCode) || $meetingCode === ''
            || ! is_string($meetingUri) || $meetingUri === '') {
            throw new GoogleMeetResponseException('Google Meetの会議スペース応答に必須項目がありません。');
        }

        return new GoogleMeetSpace(
            name: $name,
            meetingCode: $meetingCode,
            meetingUri: $meetingUri,
            activeConferenceName: is_string($activeConference) && $activeConference !== ''
                ? $activeConference
                : null,
        );
    }

    /**
     * Meet APIの会議履歴応答を画面表示用DTOへ変換する。
     *
     * @param  array<string, mixed>  $payload  Google Meet APIの会議履歴
     * @return GoogleMeetConference 変換済み会議履歴
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleMeetResponseException 必須項目が不足している場合
     */
    private function mapConference(array $payload): GoogleMeetConference
    {
        $name = $payload['name'] ?? null;
        $space = $payload['space'] ?? null;
        $startTime = $payload['startTime'] ?? null;

        if (! is_string($name) || $name === ''
            || ! is_string($space) || $space === ''
            || ! is_string($startTime) || $startTime === '') {
            throw new GoogleMeetResponseException('Google Meetの会議履歴応答に必須項目がありません。');
        }

        return new GoogleMeetConference(
            name: $name,
            spaceName: $space,
            startedAt: CarbonImmutable::parse($startTime)->setTimezone((string) config('app.timezone')),
            endedAt: $this->optionalDateTime($payload['endTime'] ?? null),
            expiresAt: $this->optionalDateTime($payload['expireTime'] ?? null),
        );
    }

    /**
     * Google APIの任意日時をアプリケーションタイムゾーンへ変換する。
     *
     * @param  mixed  $value  RFC3339日時またはnull
     * @return CarbonImmutable|null 変換済み日時
     */
    private function optionalDateTime(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->setTimezone((string) config('app.timezone'));
    }
}
