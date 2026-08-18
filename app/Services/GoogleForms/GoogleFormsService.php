<?php

namespace App\Services\GoogleForms;

use App\Data\GoogleFormDetail;
use App\Data\GoogleFormFile;
use App\Data\GoogleFormResponse;
use App\Exceptions\GoogleForms\GoogleFormsResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleForms\Support\GoogleFormsConfiguration;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/**
 * Google Forms APIとDrive APIを組み合わせ、フォーム一覧・作成・公開・回答取得を担当する。
 */
final class GoogleFormsService
{
    /**
     * @param  GoogleFormsConfiguration  $configuration  Forms API設定
     * @param  GoogleApiClient  $client  Google共通HTTPクライアント
     */
    public function __construct(
        private readonly GoogleFormsConfiguration $configuration,
        private readonly GoogleApiClient $client,
    ) {}

    /**
     * Google Forms操作へ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Forms APIに必要なOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return $this->configuration->requiredScopes();
    }

    /**
     * Google Drive上のフォームだけを更新日時順で取得する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  ?string  $keyword  フォーム名検索文字列
     * @return Collection<int, GoogleFormFile> フォーム一覧
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleFormsResponseException Drive API応答が不正な場合
     */
    public function forms(User $user, ?string $keyword = null): Collection
    {
        $queryParts = ["mimeType = 'application/vnd.google-apps.form'", 'trashed = false'];

        if ($keyword !== null && $keyword !== '') {
            $escapedKeyword = str_replace(['\\', "'"], ['\\\\', "\\'"], $keyword);
            $queryParts[] = "name contains '{$escapedKeyword}'";
        }

        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->driveFilesUrl(),
            [
                'query' => [
                    'q' => implode(' and ', $queryParts),
                    'orderBy' => 'modifiedTime desc',
                    'pageSize' => $this->configuration->pageSize(),
                    'spaces' => 'drive',
                    'fields' => 'files(id,name,modifiedTime,webViewLink,ownedByMe,capabilities(canEdit))',
                ],
            ],
        );
        $items = $response->json('files', []);

        if (! is_array($items)) {
            throw new GoogleFormsResponseException('Googleフォーム一覧を読み取れませんでした。');
        }

        return collect($items)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): GoogleFormFile => $this->mapFormFile($item))
            ->values();
    }

    /**
     * Googleフォームを作成し、説明と公開設定を必要に応じて適用する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  string  $title  フォーム名
     * @param  ?string  $description  フォーム説明
     * @param  bool  $publish  作成直後に公開する場合はtrue
     * @return GoogleFormDetail 作成したフォーム
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleFormsResponseException Forms API応答が不正な場合
     */
    public function createForm(
        User $user,
        string $title,
        ?string $description,
        bool $publish,
    ): GoogleFormDetail {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'POST',
            $this->configuration->apiUrl('forms'),
            [
                'query' => ['unpublished' => true],
                'json' => ['info' => ['title' => $title]],
            ],
        );
        $formId = $response->json('formId');

        if (! is_string($formId) || $formId === '') {
            throw new GoogleFormsResponseException('作成したGoogleフォームIDを取得できませんでした。');
        }

        if ($description !== null && $description !== '') {
            $this->updateDescription($user, $formId, $description);
        }

        if ($publish) {
            $this->setPublished($user, $formId, true);
        }

        return $this->form($user, $formId);
    }

    /**
     * Googleフォームの詳細を取得する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  string  $formId  GoogleフォームID
     * @return GoogleFormDetail フォーム詳細
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleFormsResponseException Forms API応答が不正な場合
     */
    public function form(User $user, string $formId): GoogleFormDetail
    {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->apiUrl('forms/'.rawurlencode($formId)),
        );

        return $this->mapFormDetail($response->json());
    }

    /**
     * Googleフォームの回答概要を新しい回答順で取得する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  string  $formId  GoogleフォームID
     * @return Collection<int, GoogleFormResponse> 回答概要一覧
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleFormsResponseException Forms API応答が不正な場合
     */
    public function responses(User $user, string $formId): Collection
    {
        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->apiUrl('forms/'.rawurlencode($formId).'/responses'),
            ['query' => ['pageSize' => 200]],
        );
        $items = $response->json('responses', []);

        if (! is_array($items)) {
            throw new GoogleFormsResponseException('Googleフォームの回答を読み取れませんでした。');
        }

        return collect($items)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): GoogleFormResponse => $this->mapResponse($item))
            ->sortByDesc(static fn (GoogleFormResponse $item): int => $item->submittedAt->getTimestamp())
            ->values();
    }

    /**
     * Googleフォームの公開状態と回答受付状態を切り替える。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  string  $formId  GoogleフォームID
     * @param  bool  $published  公開する場合はtrue
     */
    public function setPublished(User $user, string $formId, bool $published): void
    {
        $this->client->send(
            $user,
            $this->requiredScopes(),
            'POST',
            $this->configuration->apiUrl('forms/'.rawurlencode($formId).':setPublishSettings'),
            [
                'json' => [
                    'publishSettings' => [
                        'publishState' => [
                            'isPublished' => $published,
                            'isAcceptingResponses' => $published,
                        ],
                    ],
                ],
            ],
        );
    }

    /**
     * 新規フォームへ説明文を設定する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  string  $formId  GoogleフォームID
     * @param  string  $description  設定する説明文
     */
    private function updateDescription(User $user, string $formId, string $description): void
    {
        $this->client->send(
            $user,
            $this->requiredScopes(),
            'POST',
            $this->configuration->apiUrl('forms/'.rawurlencode($formId).':batchUpdate'),
            [
                'json' => [
                    'requests' => [[
                        'updateFormInfo' => [
                            'info' => ['description' => $description],
                            'updateMask' => 'description',
                        ],
                    ]],
                ],
            ],
        );
    }

    /**
     * Drive APIのフォームファイル応答を画面表示用DTOへ変換する。
     *
     * @param  array<string, mixed>  $payload  Drive APIファイル応答
     * @return GoogleFormFile 変換済みフォームファイル
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleFormsResponseException 必須項目が不足している場合
     */
    private function mapFormFile(array $payload): GoogleFormFile
    {
        $id = $payload['id'] ?? null;
        $name = $payload['name'] ?? null;

        if (! is_string($id) || $id === '' || ! is_string($name) || $name === '') {
            throw new GoogleFormsResponseException('Googleフォーム一覧の必須項目が不足しています。');
        }

        $modifiedTime = $payload['modifiedTime'] ?? null;
        $webViewLink = $payload['webViewLink'] ?? null;
        $capabilities = is_array($payload['capabilities'] ?? null) ? $payload['capabilities'] : [];

        return new GoogleFormFile(
            id: $id,
            name: $name,
            modifiedAt: is_string($modifiedTime) && $modifiedTime !== ''
                ? CarbonImmutable::parse($modifiedTime)->setTimezone((string) config('app.timezone'))
                : null,
            webViewLink: is_string($webViewLink) && $webViewLink !== '' ? $webViewLink : null,
            ownedByMe: ($payload['ownedByMe'] ?? false) === true,
            canEdit: ($capabilities['canEdit'] ?? false) === true,
        );
    }

    /**
     * Forms APIのフォーム応答を画面表示用DTOへ変換する。
     *
     * @param  mixed  $payload  Forms API応答
     * @return GoogleFormDetail 変換済みフォーム詳細
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleFormsResponseException 必須項目が不足している場合
     */
    private function mapFormDetail(mixed $payload): GoogleFormDetail
    {
        if (! is_array($payload)) {
            throw new GoogleFormsResponseException('Googleフォーム詳細を読み取れませんでした。');
        }

        $formId = $payload['formId'] ?? null;
        $info = is_array($payload['info'] ?? null) ? $payload['info'] : [];
        $title = $info['title'] ?? null;
        $documentTitle = $info['documentTitle'] ?? $title;

        if (! is_string($formId) || $formId === ''
            || ! is_string($title) || $title === ''
            || ! is_string($documentTitle) || $documentTitle === '') {
            throw new GoogleFormsResponseException('Googleフォーム詳細の必須項目が不足しています。');
        }

        $description = $info['description'] ?? null;
        $responderUri = $payload['responderUri'] ?? null;
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $quizSettings = is_array($settings['quizSettings'] ?? null) ? $settings['quizSettings'] : [];
        $publishSettings = is_array($payload['publishSettings'] ?? null)
            ? $payload['publishSettings']
            : null;
        $publishState = is_array($publishSettings['publishState'] ?? null)
            ? $publishSettings['publishState']
            : [];

        return new GoogleFormDetail(
            id: $formId,
            title: $title,
            documentTitle: $documentTitle,
            description: is_string($description) && $description !== '' ? $description : null,
            responderUri: is_string($responderUri) && $responderUri !== '' ? $responderUri : null,
            itemCount: count($items),
            isQuiz: ($quizSettings['isQuiz'] ?? false) === true,
            supportsPublishing: $publishSettings !== null,
            isPublished: ($publishState['isPublished'] ?? false) === true,
            isAcceptingResponses: ($publishState['isAcceptingResponses'] ?? false) === true,
        );
    }

    /**
     * Forms APIの回答応答を画面表示用DTOへ変換する。
     *
     * @param  array<string, mixed>  $payload  Forms API回答応答
     * @return GoogleFormResponse 変換済み回答概要
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleFormsResponseException 必須項目が不足している場合
     */
    private function mapResponse(array $payload): GoogleFormResponse
    {
        $responseId = $payload['responseId'] ?? null;
        $createTime = $payload['createTime'] ?? null;
        $lastSubmittedTime = $payload['lastSubmittedTime'] ?? null;

        if (! is_string($responseId) || $responseId === ''
            || ! is_string($createTime) || $createTime === ''
            || ! is_string($lastSubmittedTime) || $lastSubmittedTime === '') {
            throw new GoogleFormsResponseException('Googleフォーム回答の必須項目が不足しています。');
        }

        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        $respondentEmail = $payload['respondentEmail'] ?? null;
        $totalScore = $payload['totalScore'] ?? null;

        return new GoogleFormResponse(
            id: $responseId,
            respondentEmail: is_string($respondentEmail) && $respondentEmail !== ''
                ? $respondentEmail
                : null,
            createdAt: CarbonImmutable::parse($createTime)->setTimezone((string) config('app.timezone')),
            submittedAt: CarbonImmutable::parse($lastSubmittedTime)->setTimezone((string) config('app.timezone')),
            answerCount: count($answers),
            totalScore: is_numeric($totalScore) ? (float) $totalScore : null,
        );
    }
}
