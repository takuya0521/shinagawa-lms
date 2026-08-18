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
 * 全ロール共通Google Meet画面の軽量表示、会議作成、履歴取得を確認する。
 */
final class GoogleMeetIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テストでMeet API設定を登録し、意図しない外部通信を禁止する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureGoogleWorkspace();
        Cache::flush();
        Http::preventStrayRequests();
    }

    /**
     * 連携済み利用者でもMeet画面本体の表示時にはGoogle APIを呼ばないことを確認する。
     *
     * 前提: Meet権限を含む連携済み教員を準備する。
     * 処理: Google Meetの通常画面URLへアクセスする。
     * 期待結果: HTTP 200と読み込み表示だけが返り、Google API通信は0件となる。
     */
    public function test_meet_page_shell_does_not_wait_for_google_api(): void
    {
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()
            ->for($teacher)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.meet.index'))
            ->assertOk()
            ->assertSeeText('Google Meetを読み込んでいます');

        Http::assertNothingSent();
    }

    /**
     * LMSからGoogle Meet会議スペースを作成できることを確認する。
     *
     * 前提: Meet権限を持つ管理者とspaces.createの正常応答を準備する。
     * 処理: 会議作成ルートへPOSTする。
     * 期待結果: Meet一覧へ戻り、作成した会議コードと参加URLがセッションへ保存される。
     */
    public function test_connected_user_can_create_meet_space(): void
    {
        Http::fake([
            'https://meet.googleapis.com/v2/spaces' => Http::response([
                'name' => 'spaces/space001',
                'meetingCode' => 'abc-defg-hij',
                'meetingUri' => 'https://meet.google.com/abc-defg-hij',
            ]),
        ]);
        $admin = $this->user(UserRole::Admin);
        GoogleDriveConnection::factory()
            ->for($admin)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($admin)
            ->post(route('google-workspace.meet.store'))
            ->assertRedirect(route('google-workspace.meet.index'))
            ->assertSessionHas('google_meet_created_space.meeting_code', 'abc-defg-hij')
            ->assertSessionHas(
                'google_meet_created_space.meeting_uri',
                'https://meet.google.com/abc-defg-hij',
            );

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://meet.googleapis.com/v2/spaces');
    }

    /**
     * 会議コード検索と直近会議履歴をMeet画面へ表示できることを確認する。
     *
     * 前提: spaces.getとconferenceRecords.listの正常応答を準備する。
     * 処理: 会議コード付きの非同期表示ルートへアクセスする。
     * 期待結果: 会議コード、参加導線、会議履歴が表示される。
     */
    public function test_meet_content_can_search_space_and_show_conferences(): void
    {
        Http::fake([
            'https://meet.googleapis.com/v2/spaces/abc-defg-hij' => Http::response([
                'name' => 'spaces/space001',
                'meetingCode' => 'abc-defg-hij',
                'meetingUri' => 'https://meet.google.com/abc-defg-hij',
            ]),
            'https://meet.googleapis.com/v2/conferenceRecords*' => Http::response([
                'conferenceRecords' => [[
                    'name' => 'conferenceRecords/conference001',
                    'space' => 'spaces/space001',
                    'startTime' => '2026-08-10T01:00:00Z',
                    'endTime' => '2026-08-10T02:00:00Z',
                    'expireTime' => '2026-09-10T02:00:00Z',
                ]],
            ]),
        ]);
        $student = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()
            ->for($student)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($student)
            ->get(route('google-workspace.meet.content', ['meeting_code' => 'abc-defg-hij']))
            ->assertOk()
            ->assertSeeText('abc-defg-hij')
            ->assertSeeText('Meetに参加')
            ->assertSeeText('60分');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/conferenceRecords')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['filter'] ?? null) === 'space.meeting_code = "abc-defg-hij"';
        });
    }

    /**
     * Google WorkspaceとMeet APIのテスト設定を登録する。
     */
    private function configureGoogleWorkspace(): void
    {
        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.scopes', $this->scopes());
        config()->set('services.google_meet.api_uri', 'https://meet.googleapis.com/v2');
        config()->set(
            'services.google_meet.scope',
            'https://www.googleapis.com/auth/meetings.space.created',
        );
        config()->set('services.google_meet.page_size', 25);
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
