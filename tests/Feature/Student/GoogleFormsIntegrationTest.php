<?php

namespace Tests\Feature\Student;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 全ロール共通Google Forms画面の一覧、作成、詳細、公開設定を確認する。
 */
final class GoogleFormsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テストでFormsとDrive API設定を登録し、意図しない外部通信を禁止する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureGoogleWorkspace();
        Cache::flush();
        Http::preventStrayRequests();
    }

    /**
     * 連携済み利用者でもForms画面本体の表示時にはGoogle APIを呼ばないことを確認する。
     *
     * 前提: Drive権限を持つ連携済み教員を準備する。
     * 処理: Google Formsの通常画面URLへアクセスする。
     * 期待結果: HTTP 200と読み込み表示だけが返り、Google API通信は0件となる。
     */
    public function test_forms_page_shell_does_not_wait_for_google_api(): void
    {
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()
            ->for($teacher)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.forms.index'))
            ->assertOk()
            ->assertSeeText('Google Formsを読み込んでいます');

        Http::assertNothingSent();
    }

    /**
     * Drive上のGoogleフォームだけを一覧表示できることを確認する。
     *
     * 前提: Forms MIMEタイプのDriveファイル応答を準備する。
     * 処理: Forms一覧の非同期表示ルートへアクセスする。
     * 期待結果: フォーム名とGoogle編集導線が表示される。
     */
    public function test_connected_user_can_view_google_form_files(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [[
                    'id' => 'form001',
                    'name' => '進路希望アンケート',
                    'modifiedTime' => '2026-08-10T01:00:00Z',
                    'webViewLink' => 'https://docs.google.com/forms/d/form001/edit',
                    'ownedByMe' => true,
                    'capabilities' => ['canEdit' => true],
                ]],
            ]),
        ]);
        $student = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()
            ->for($student)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($student)
            ->get(route('google-workspace.forms.content'))
            ->assertOk()
            ->assertSeeText('進路希望アンケート')
            ->assertSeeText('Googleで編集');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/drive/v3/files')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains(
                (string) ($query['q'] ?? ''),
                "mimeType = 'application/vnd.google-apps.form'",
            );
        });
    }

    /**
     * フォーム作成後に説明と公開設定を適用できることを確認する。
     *
     * 前提: forms.create、batchUpdate、setPublishSettings、forms.getの正常応答を準備する。
     * 処理: タイトル・説明・公開指定をForms作成ルートへPOSTする。
     * 期待結果: 詳細画面へ遷移し、公開設定APIが実行される。
     */
    public function test_connected_user_can_create_and_publish_form(): void
    {
        Http::fake(function (Request $request) {
            if (str_starts_with($request->url(), 'https://forms.googleapis.com/v1/forms?')) {
                return Http::response([
                    'formId' => 'form001',
                    'info' => ['title' => '学校アンケート', 'documentTitle' => '学校アンケート'],
                ]);
            }

            if (str_ends_with($request->url(), '/forms/form001:batchUpdate')) {
                return Http::response(['replies' => [[]]]);
            }

            if (str_ends_with($request->url(), '/forms/form001:setPublishSettings')) {
                return Http::response([
                    'formId' => 'form001',
                    'publishSettings' => [
                        'publishState' => [
                            'isPublished' => true,
                            'isAcceptingResponses' => true,
                        ],
                    ],
                ]);
            }

            if (str_ends_with($request->url(), '/forms/form001')) {
                return Http::response($this->formPayload());
            }

            return Http::response([], 404);
        });
        $admin = $this->user(UserRole::Admin);
        GoogleDriveConnection::factory()
            ->for($admin)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($admin)
            ->post(route('google-workspace.forms.store'), [
                'title' => '学校アンケート',
                'description' => '学校生活について回答してください。',
                'publish' => '1',
            ])
            ->assertRedirect(route('google-workspace.forms.show', 'form001'));

        Http::assertSent(function (Request $request): bool {
            if (! str_ends_with($request->url(), ':setPublishSettings')) {
                return false;
            }

            return $request['publishSettings']['publishState']['isPublished'] === true
                && $request['publishSettings']['publishState']['isAcceptingResponses'] === true;
        });
    }

    /**
     * フォーム詳細と回答概要を非同期表示できることを確認する。
     *
     * 前提: forms.getとresponses.listの正常応答を準備する。
     * 処理: Forms詳細の非同期表示ルートへアクセスする。
     * 期待結果: フォーム名、公開状態、回答者メールが表示される。
     */
    public function test_form_detail_content_shows_publish_state_and_responses(): void
    {
        Http::fake([
            'https://forms.googleapis.com/v1/forms/form001/responses*' => Http::response([
                'responses' => [[
                    'responseId' => 'response001',
                    'createTime' => '2026-08-10T01:00:00Z',
                    'lastSubmittedTime' => '2026-08-10T01:05:00Z',
                    'respondentEmail' => 'student@example.com',
                    'answers' => ['question001' => ['textAnswers' => ['answers' => [['value' => '回答']]]]],
                ]],
            ]),
            'https://forms.googleapis.com/v1/forms/form001' => Http::response($this->formPayload()),
        ]);
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()
            ->for($teacher)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.forms.show-content', 'form001'))
            ->assertOk()
            ->assertSeeText('学校アンケート')
            ->assertSeeText('公開中')
            ->assertSeeText('student@example.com');
    }

    /**
     * Google Workspace、Drive、Forms APIのテスト設定を登録する。
     */
    private function configureGoogleWorkspace(): void
    {
        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.scopes', $this->scopes());
        config()->set('services.google_drive.files_uri', 'https://www.googleapis.com/drive/v3/files');
        config()->set('services.google_drive.scope', 'https://www.googleapis.com/auth/drive');
        config()->set('services.google_forms.api_uri', 'https://forms.googleapis.com/v1');
        config()->set('services.google_forms.page_size', 50);
    }

    /**
     * Forms APIの詳細応答を返す。
     *
     * @return array<string, mixed> テスト用フォーム応答
     */
    private function formPayload(): array
    {
        return [
            'formId' => 'form001',
            'info' => [
                'title' => '学校アンケート',
                'documentTitle' => '学校アンケート',
                'description' => '学校生活について回答してください。',
            ],
            'settings' => ['quizSettings' => ['isQuiz' => false]],
            'items' => [['itemId' => 'question001']],
            'responderUri' => 'https://docs.google.com/forms/d/e/form001/viewform',
            'publishSettings' => [
                'publishState' => [
                    'isPublished' => true,
                    'isAcceptingResponses' => true,
                ],
            ],
        ];
    }

    /**
     * Google Workspace管理操作で要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> OAuthスコープ一覧
     */
    private function scopes(): array
    {
        return [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/chat.spaces',
            'https://www.googleapis.com/auth/chat.memberships',
            'https://www.googleapis.com/auth/chat.messages',
            'https://www.googleapis.com/auth/chat.messages.reactions',
            'https://www.googleapis.com/auth/chat.delete',
            'https://www.googleapis.com/auth/meetings.space.created',
        ];
    }

    /**
     * 指定ロールの有効なテストユーザーを作成する。
     *
     * @param  UserRole  $role  作成するロール
     * @return User 作成したユーザー
     */
    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => UserStatus::Active]);
    }
}
