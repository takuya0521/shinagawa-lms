<?php

namespace Tests\Feature\GoogleWorkspace;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Google API共通クライアントの並列送信・401再試行・キャッシュ整合性を確認する。
 */
final class GoogleApiClientTest extends TestCase
{
    use RefreshDatabase;

    private const DRIVE_SCOPE = 'https://www.googleapis.com/auth/drive';

    /**
     * OAuth更新とGoogle API送信に必要なテスト設定を登録する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.token_uri', 'https://oauth2.googleapis.com/token');
        config()->set('services.google_workspace.scopes', [self::DRIVE_SCOPE]);
        Http::preventStrayRequests();
    }

    /**
     * 並列APIに401が含まれる場合もトークン更新後の再送を1回に限定することを確認する。
     *
     * 前提: 初回APIが401、トークン更新と2回目APIが成功する応答を準備する。
     * 処理: sendManyで1件のGoogle APIを送信する。
     * 期待結果: APIは2回、トークン更新は1回だけ呼ばれ、成功応答が返る。
     */
    public function test_send_many_refreshes_token_once_after_unauthorized_response(): void
    {
        $apiCalls = 0;
        $tokenCalls = 0;
        Http::fake(function (Request $request) use (&$apiCalls, &$tokenCalls) {
            if ($request->url() === 'https://oauth2.googleapis.com/token') {
                $tokenCalls++;

                return Http::response([
                    'access_token' => 'refreshed-access-token',
                    'expires_in' => 3600,
                ]);
            }

            if (str_starts_with($request->url(), 'https://www.googleapis.com/drive/v3/files')) {
                $apiCalls++;

                return $apiCalls === 1
                    ? Http::response(['error' => ['message' => 'expired']], 401)
                    : Http::response(['files' => []]);
            }

            return Http::response([], 404);
        });
        $user = $this->connectedUser();

        $responses = app(GoogleApiClient::class)->sendMany(
            $user,
            [self::DRIVE_SCOPE],
            [
                'files' => [
                    'method' => 'GET',
                    'url' => 'https://www.googleapis.com/drive/v3/files',
                ],
            ],
        );

        $this->assertSame(200, $responses['files']->status());
        $this->assertSame(2, $apiCalls);
        $this->assertSame(1, $tokenCalls);
        $this->assertSame(
            'refreshed-access-token',
            $user->googleDriveConnection?->fresh()?->access_token,
        );
    }

    /**
     * 更新API成功後に対象サービスの表示キャッシュが破棄されることを確認する。
     *
     * 前提: Drive一覧キャッシュと成功する更新APIを準備する。
     * 処理: 共通クライアントからDriveのPATCHを送信する。
     * 期待結果: 更新後に既存のDriveキャッシュを参照できなくなる。
     */
    public function test_successful_mutation_invalidates_drive_cache(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/file001*' => Http::response([
                'id' => 'file001',
                'name' => '更新後',
            ]),
        ]);
        $user = $this->connectedUser();
        $cache = app(GoogleWorkspaceCache::class);
        $cache->put($user, 'drive', 'files', 300, ['before']);

        app(GoogleApiClient::class)->send(
            $user,
            [self::DRIVE_SCOPE],
            'PATCH',
            'https://www.googleapis.com/drive/v3/files/file001',
            ['json' => ['name' => '更新後']],
        );

        $this->assertNull($cache->get($user, 'drive', 'files'));
    }

    /**
     * 有効なGoogle Workspace接続を持つ教員を作成する。
     *
     * @return User 接続情報をリレーションへ設定済みの利用者
     */
    private function connectedUser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);
        $connection = GoogleDriveConnection::factory()->for($user)->create([
            'access_token' => 'expired-access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => now()->addHour(),
            'scope' => self::DRIVE_SCOPE,
        ]);
        $user->setRelation('googleDriveConnection', $connection);

        return $user;
    }
}
