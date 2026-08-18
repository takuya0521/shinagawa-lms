<?php

namespace Tests\Feature\Student;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use App\Services\GoogleDrive\GoogleDriveMutationService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 全ロール共通Google Drive管理画面のOAuth、一覧、共有ドライブ表示を確認する。
 */
final class GoogleDriveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テストで同じGoogle Workspace設定を使用し、意図しない外部通信を禁止する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureGoogleWorkspace();
        Cache::flush();
        Http::preventStrayRequests();
    }

    /**
     * 管理者・教員・生徒が共通Drive画面を利用できることを確認する。
     *
     * 前提: Google未連携の各ロールユーザーを準備する。
     * 処理: 共通Drive画面へアクセスする。
     * 期待結果: 全ロールでHTTP 200となり、連携ボタンが表示される。
     */
    public function test_all_roles_can_view_common_google_drive_screen(): void
    {
        foreach ([UserRole::Admin, UserRole::Teacher, UserRole::Student] as $role) {
            $user = $this->user($role);

            $this->actingAs($user)
                ->get(route('google-workspace.drive.index'))
                ->assertOk()
                ->assertSeeText('Google Drive')
                ->assertSeeText('Google Workspaceと連携');
        }
    }

    /**
     * 連携済み利用者でもDrive画面本体の表示時にはGoogle APIを呼ばないことを確認する。
     *
     * 前提: 全管理スコープを持つ生徒を準備し、外部通信を禁止する。
     * 処理: Google Driveの通常画面URLへアクセスする。
     * 期待結果: HTTP 200と読み込み表示だけが返り、Google API通信は0件となる。
     */
    public function test_drive_page_shell_does_not_wait_for_google_api(): void
    {
        $user = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()->for($user)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($user)
            ->get(route('google-workspace.drive.index'))
            ->assertOk()
            ->assertSeeText('Google Driveを読み込んでいます');

        Http::assertNothingSent();
    }

    /**
     * OAuth開始時にDrive・Calendar・Chat・Meetの管理スコープをまとめて要求することを確認する。
     *
     * 前提: Google未連携の有効な教員ユーザーを準備する。
     * 処理: 共通OAuth開始URLへアクセスする。
     * 期待結果: 8種類の管理スコープを含むGoogle認可URLへ遷移する。
     */
    public function test_workspace_oauth_requests_all_management_scopes(): void
    {
        $response = $this->actingAs($this->user(UserRole::Teacher))
            ->get(route('google-workspace.connect'));
        $location = $response->headers->get('Location');

        $this->assertIsString($location);
        $query = parse_url($location, PHP_URL_QUERY);
        $this->assertIsString($query);
        parse_str($query, $parameters);
        $scopeParameter = $parameters['scope'] ?? '';
        $this->assertIsString($scopeParameter);
        $scopes = explode(' ', $scopeParameter);

        foreach ($this->scopes() as $scope) {
            $this->assertContains($scope, $scopes);
        }

        $response->assertSessionHas('google_workspace_oauth_state');
    }

    /**
     * Google Workspaceの既存権限を含む500文字超のスコープを保存できることを確認する。
     *
     * 前提: Googleが過去の読み取り権限も含めて返す状態を再現する。
     * 処理: 500文字を超えるスコープ文字列を連携情報へ保存する。
     * 期待結果: PostgreSQLの文字数制限エラーが発生せず、全文が保持される。
     */
    public function test_connection_can_store_scope_longer_than_500_characters(): void
    {
        $scope = implode(' ', [
            'https://www.googleapis.com/auth/calendar.events.readonly',
            'https://www.googleapis.com/auth/chat.messages.readonly',
            'https://www.googleapis.com/auth/chat.spaces',
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/chat.delete',
            'https://www.googleapis.com/auth/meetings.space.created',
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/chat.messages',
            'https://www.googleapis.com/auth/chat.messages.reactions',
            'https://www.googleapis.com/auth/chat.spaces.readonly',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            'https://www.googleapis.com/auth/chat.memberships',
        ]);

        $this->assertGreaterThan(500, strlen($scope));

        $connection = GoogleDriveConnection::factory()
            ->for($this->user(UserRole::Student))
            ->create(['scope' => $scope]);

        $this->assertSame($scope, $connection->fresh()?->scope);
    }

    /**
     * 連携済みユーザーにマイドライブと共有ドライブが表示されることを確認する。
     *
     * 前提: 全管理スコープを持つ生徒とDrive APIの正常応答を準備する。
     * 処理: 共通Drive画面へアクセスする。
     * 期待結果: ファイル名と共有ドライブ名がLMS内へ表示される。
     */
    public function test_connected_user_can_view_files_and_shared_drives(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [[
                    'id' => 'file001',
                    'name' => '授業資料.pdf',
                    'mimeType' => 'application/pdf',
                    'modifiedTime' => '2026-08-06T01:00:00Z',
                    'trashed' => false,
                ]],
            ]),
            'https://www.googleapis.com/drive/v3/drives*' => Http::response([
                'drives' => [['id' => 'drive001', 'name' => '学校共有ドライブ']],
            ]),
        ]);
        $user = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()->for($user)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($user)
            ->get(route('google-workspace.drive.content'))
            ->assertOk()
            ->assertSeeText('授業資料.pdf')
            ->assertSeeText('学校共有ドライブ');

        Http::assertSent(function (Request $request): bool {
            if (! str_starts_with($request->url(), 'https://www.googleapis.com/drive/v3/files')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['includeItemsFromAllDrives'] ?? null) === 'true'
                && ($query['supportsAllDrives'] ?? null) === 'true';
        });
    }

    /**
     * 表示場所を切り替えても共有ドライブ一覧を再取得しないことを確認する。
     *
     * 前提: 同一利用者向けにファイル一覧と共有ドライブの正常応答を準備する。
     * 処理: マイドライブ表示後に最近使用したアイテムへ移動する。
     * 期待結果: files.listは表示条件ごとに呼ばれ、drives.listはSWRキャッシュから再利用される。
     */
    public function test_shared_drive_catalog_is_reused_between_drive_views(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response(['files' => []]),
            'https://www.googleapis.com/drive/v3/drives*' => Http::response(['drives' => []]),
        ]);
        $user = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()->for($user)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($user)
            ->get(route('google-workspace.drive.content'))
            ->assertOk();
        $this->get(route('google-workspace.drive.content', ['view' => 'recent']))
            ->assertOk();

        $recorded = Http::recorded();
        $fileRequests = collect($recorded)
            ->filter(static fn (array $exchange): bool => str_starts_with(
                $exchange[0]->url(),
                'https://www.googleapis.com/drive/v3/files',
            ));
        $sharedDriveRequests = collect($recorded)
            ->filter(static fn (array $exchange): bool => str_starts_with(
                $exchange[0]->url(),
                'https://www.googleapis.com/drive/v3/drives',
            ));

        $this->assertCount(2, $fileRequests);
        $this->assertCount(1, $sharedDriveRequests);
    }

    /**
     * Drive詳細画面でファイル・共有・コメント・版履歴をまとめて表示できることを確認する。
     *
     * 前提: 全管理スコープを持つ生徒と、詳細画面が使用する4種類のDrive API応答を準備する。
     * 処理: 対象ファイルの詳細画面へアクセスする。
     * 期待結果: HTTP 200となり、ファイル名・共有先・コメント・版履歴がLMS内へ表示される。
     */
    public function test_connected_user_can_view_drive_file_detail(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/file001?*' => Http::response([
                'id' => 'file001',
                'name' => '授業資料.pdf',
                'mimeType' => 'application/pdf',
                'modifiedTime' => '2026-08-06T01:00:00Z',
                'trashed' => false,
                'capabilities' => [
                    'canEdit' => true,
                    'canTrash' => true,
                    'canDelete' => true,
                    'canShare' => true,
                    'canDownload' => true,
                ],
            ]),
            'https://www.googleapis.com/drive/v3/files/file001/permissions*' => Http::response([
                'permissions' => [[
                    'id' => 'permission001',
                    'type' => 'user',
                    'role' => 'reader',
                    'displayName' => '共有ユーザー',
                    'emailAddress' => 'shared@example.com',
                ]],
            ]),
            'https://www.googleapis.com/drive/v3/files/file001/comments*' => Http::response([
                'comments' => [[
                    'id' => 'comment001',
                    'content' => '確認しました',
                    'createdTime' => '2026-08-06T02:00:00Z',
                    'resolved' => false,
                    'author' => ['displayName' => '担当教員'],
                    'replies' => [],
                ]],
            ]),
            'https://www.googleapis.com/drive/v3/files/file001/revisions*' => Http::response([
                'revisions' => [[
                    'id' => 'revision001',
                    'modifiedTime' => '2026-08-06T01:30:00Z',
                    'lastModifyingUser' => ['displayName' => '担当教員'],
                    'size' => '1024',
                    'keepForever' => false,
                ]],
            ]),
        ]);
        $user = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()->for($user)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($user)
            ->get(route('google-workspace.drive.show-content', ['fileId' => 'file001']))
            ->assertOk()
            ->assertSeeText('授業資料.pdf')
            ->assertSeeText('共有ユーザー')
            ->assertSeeText('確認しました')
            ->assertSeeText('担当教員');
    }

    /**
     * 再開可能アップロード成功後にDrive一覧キャッシュが破棄されることを確認する。
     *
     * 前提: アップロード開始・本体送信が成功する応答と既存一覧キャッシュを準備する。
     * 処理: GoogleDriveMutationServiceからファイルをアップロードする。
     * 期待結果: 作成されたファイルが返り、古いDrive一覧キャッシュを参照できなくなる。
     */
    public function test_resumable_upload_invalidates_drive_cache(): void
    {
        Http::fake([
            'https://www.googleapis.com/upload/drive/v3/files*' => Http::response(
                [],
                200,
                ['Location' => 'https://www.googleapis.com/upload-session/file001'],
            ),
            'https://www.googleapis.com/upload-session/file001' => Http::response([
                'id' => 'file001',
                'name' => '課題.txt',
                'mimeType' => 'text/plain',
            ]),
        ]);
        $user = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()->for($user)->create(['scope' => implode(' ', $this->scopes())]);
        $cache = app(GoogleWorkspaceCache::class);
        $cache->put($user, 'drive', 'files', 300, ['before']);

        $file = app(GoogleDriveMutationService::class)->upload(
            $user,
            UploadedFile::fake()->createWithContent('課題.txt', '提出内容'),
            null,
        );

        $this->assertSame('file001', $file->id);
        $this->assertNull($cache->get($user, 'drive', 'files'));
    }

    /**
     * Google Workspaceと各APIのテスト設定を登録する。
     */
    private function configureGoogleWorkspace(): void
    {
        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.authorization_uri', 'https://accounts.google.com/o/oauth2/v2/auth');
        config()->set('services.google_workspace.token_uri', 'https://oauth2.googleapis.com/token');
        config()->set('services.google_workspace.revoke_uri', 'https://oauth2.googleapis.com/revoke');
        config()->set('services.google_workspace.scopes', $this->scopes());
        config()->set('services.google_drive.files_uri', 'https://www.googleapis.com/drive/v3/files');
        config()->set('services.google_drive.upload_uri', 'https://www.googleapis.com/upload/drive/v3/files');
        config()->set('services.google_drive.drives_uri', 'https://www.googleapis.com/drive/v3/drives');
        config()->set('services.google_drive.scope', 'https://www.googleapis.com/auth/drive');
        config()->set('services.google_drive.page_size', 30);
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
