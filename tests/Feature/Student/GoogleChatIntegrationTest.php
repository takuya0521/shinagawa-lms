<?php

namespace Tests\Feature\Student;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use App\Services\GoogleChat\GoogleChatMembershipService;
use App\Services\GoogleChat\GoogleChatMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 全ロール共通Google Chat管理画面のスペース表示と会話作成入力を確認する。
 */
final class GoogleChatIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テストでChat API設定を登録し、意図しない外部通信を禁止する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.authorization_uri', 'https://accounts.google.com/o/oauth2/v2/auth');
        config()->set('services.google_workspace.token_uri', 'https://oauth2.googleapis.com/token');
        config()->set('services.google_workspace.revoke_uri', 'https://oauth2.googleapis.com/revoke');
        config()->set('services.google_workspace.scopes', $this->scopes());
        config()->set('services.google_chat.api_uri', 'https://chat.googleapis.com/v1');
        config()->set('services.google_chat.upload_uri', 'https://chat.googleapis.com/upload/v1');
        config()->set('services.google_chat.spaces_scope', 'https://www.googleapis.com/auth/chat.spaces');
        config()->set('services.google_chat.memberships_scope', 'https://www.googleapis.com/auth/chat.memberships');
        config()->set('services.google_chat.messages_scope', 'https://www.googleapis.com/auth/chat.messages');
        config()->set(
            'services.google_chat.reactions_scope',
            'https://www.googleapis.com/auth/chat.messages.reactions',
        );
        config()->set('services.google_chat.delete_scope', 'https://www.googleapis.com/auth/chat.delete');
        config()->set('services.google_chat.space_page_size', 50);
        config()->set('services.google_chat.message_page_size', 100);
        Cache::flush();
        Http::preventStrayRequests();
    }

    /**
     * 連携済み管理者に参加中スペースが表示されることを確認する。
     *
     * 前提: 全管理スコープを持つ管理者とChat APIの正常応答を準備する。
     * 処理: 共通Chat画面へアクセスする。
     * 期待結果: スペース名とチャット選択案内が表示される。
     */
    public function test_connected_admin_can_view_chat_spaces(): void
    {
        Http::fake([
            'https://chat.googleapis.com/v1/spaces*' => Http::response([
                'spaces' => [[
                    'name' => 'spaces/AAAABBBBCCCC',
                    'displayName' => '教職員連絡',
                    'spaceType' => 'SPACE',
                    'spaceUri' => 'https://chat.google.com/room/AAAABBBBCCCC',
                ]],
            ]),
        ]);
        $admin = $this->user(UserRole::Admin);
        GoogleDriveConnection::factory()->for($admin)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($admin)
            ->get(route('google-workspace.chat.content'))
            ->assertOk()
            ->assertSeeText('教職員連絡')
            ->assertSeeText('チャットを選択してください');

        Http::assertSent(function (Request $request): bool {
            if (! str_starts_with($request->url(), 'https://chat.googleapis.com/v1/spaces')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $fields = $query['fields'] ?? null;

            return is_string($fields)
                && ! str_contains($fields, 'membershipState')
                && ! str_contains($fields, 'permissionSettings');
        });
    }

    /**
     * 連携済み利用者でもChat画面本体の表示時にはGoogle APIを呼ばないことを確認する。
     *
     * 前提: 全管理スコープを持つ管理者を準備し、外部通信を禁止する。
     * 処理: Google Chatの通常画面URLへアクセスする。
     * 期待結果: HTTP 200と読み込み表示だけが返り、Google API通信は0件となる。
     */
    public function test_chat_page_shell_does_not_wait_for_google_api(): void
    {
        $admin = $this->user(UserRole::Admin);
        GoogleDriveConnection::factory()->for($admin)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($admin)
            ->get(route('google-workspace.chat.index'))
            ->assertOk()
            ->assertSeeText('Google Chatを読み込んでいます');

        Http::assertNothingSent();
    }

    /**
     * メンバーパネルを開くまでmembers.listを呼ばないことを確認する。
     *
     * 前提: スペース詳細、メッセージ、固定表示、メンバーの正常応答を準備する。
     * 処理: 通常の会話画面を表示した後、メンバーパネル付きで再表示する。
     * 期待結果: 初回はmembers.listを省略し、パネル表示時だけ1回取得する。
     */
    public function test_members_are_loaded_only_when_members_panel_is_opened(): void
    {
        Http::fake([
            'https://chat.googleapis.com/v1/spaces/AAAABBBBCCCC/members*' => Http::response([
                'memberships' => [],
            ]),
            'https://chat.googleapis.com/v1/spaces/AAAABBBBCCCC/messages*' => Http::response([
                'messages' => [],
            ]),
            'https://chat.googleapis.com/v1/spaces/AAAABBBBCCCC/messagePins*' => Http::response([
                'messagePins' => [],
            ]),
            'https://chat.googleapis.com/v1/spaces/AAAABBBBCCCC*' => Http::response([
                'name' => 'spaces/AAAABBBBCCCC',
                'displayName' => '教職員連絡',
                'spaceType' => 'SPACE',
                'spaceUri' => 'https://chat.google.com/room/AAAABBBBCCCC',
            ]),
            'https://chat.googleapis.com/v1/spaces*' => Http::response([
                'spaces' => [[
                    'name' => 'spaces/AAAABBBBCCCC',
                    'displayName' => '教職員連絡',
                    'spaceType' => 'SPACE',
                    'spaceUri' => 'https://chat.google.com/room/AAAABBBBCCCC',
                ]],
            ]),
        ]);
        $admin = $this->user(UserRole::Admin);
        GoogleDriveConnection::factory()->for($admin)->create(['scope' => implode(' ', $this->scopes())]);
        $showUrl = route('google-workspace.chat.show-content', 'AAAABBBBCCCC');

        $this->actingAs($admin)
            ->get($showUrl)
            ->assertOk()
            ->assertSeeText('教職員連絡');

        $this->assertCount(0, collect(Http::recorded())->filter(
            static fn (array $exchange): bool => str_contains($exchange[0]->url(), '/members'),
        ));

        $this->get($showUrl.'?panel=members')
            ->assertOk();

        $this->assertCount(1, collect(Http::recorded())->filter(
            static fn (array $exchange): bool => str_contains($exchange[0]->url(), '/members'),
        ));
    }

    /**
     * Chat一覧系APIの真偽値をGoogleが受理する文字列で送信することを確認する。
     *
     * 前提: 全管理スコープを持つ管理者と空のメンバー・メッセージ応答を準備する。
     * 処理: メンバー一覧とメッセージ一覧を取得する。
     * 期待結果: trueが数値1ではなく小文字文字列として送信される。
     */
    public function test_chat_boolean_query_values_are_sent_as_lowercase_literals(): void
    {
        Http::fake([
            'https://chat.googleapis.com/v1/spaces/AAAABBBBCCCC/members*' => Http::response([
                'memberships' => [],
            ]),
            'https://chat.googleapis.com/v1/spaces/AAAABBBBCCCC/messages*' => Http::response([
                'messages' => [],
            ]),
        ]);
        $admin = $this->user(UserRole::Admin);
        GoogleDriveConnection::factory()->for($admin)->create(['scope' => implode(' ', $this->scopes())]);
        $membershipService = app(GoogleChatMembershipService::class);
        $messageService = app(GoogleChatMessageService::class);

        $membershipService->memberships($admin, 'AAAABBBBCCCC');
        $messageService->messages($admin, 'AAAABBBBCCCC');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/members')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['showGroups'] ?? null) === 'true'
                && ($query['showInvited'] ?? null) === 'true';
        });

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/messages')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['showDeleted'] ?? null) === 'true';
        });
    }

    /**
     * DM作成時に相手を複数指定できないことを確認する。
     *
     * 前提: 全管理スコープを持つ生徒を準備する。
     * 処理: 相手を2件指定してDM作成リクエストを送る。
     * 期待結果: 入力エラーとなりGoogle Chat APIは呼び出されない。
     */
    public function test_direct_message_requires_exactly_one_other_member(): void
    {
        $student = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()->for($student)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($student)
            ->from(route('google-workspace.chat.index'))
            ->post(route('google-workspace.chat.spaces.store'), [
                'space_type' => 'DIRECT_MESSAGE',
                'members' => "first@example.com\nsecond@example.com",
            ])
            ->assertRedirect(route('google-workspace.chat.index'))
            ->assertSessionHasErrors('members');

        Http::assertNothingSent();
    }

    /**
     * 管理操作で要求するOAuthスコープ一覧を返す。
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
