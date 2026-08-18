<?php

namespace Tests\Unit\Services\GoogleWorkspace;

use App\Models\User;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCacheInvalidator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Google WorkspaceのSWRキャッシュが再利用・無効化されることを確認する。
 */
final class GoogleWorkspaceCacheTest extends TestCase
{
    /**
     * テストごとに共有キャッシュを空にする。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
    }

    /**
     * 同じ利用者・サービス・条件では取得処理を繰り返さないことを確認する。
     *
     * 前提: 同一キーへ値を返す取得処理を用意する。
     * 処理: rememberを2回実行する。
     * 期待結果: 取得処理は1回だけ実行され、2回目は保存済み値が返る。
     */
    public function test_remember_reuses_cached_value(): void
    {
        $user = $this->user(10);
        $cache = app(GoogleWorkspaceCache::class);
        $resolved = 0;
        $resolver = static function () use (&$resolved): array {
            $resolved++;

            return ['value' => 'cached'];
        };

        $first = $cache->remember($user, 'drive', 'shared-drives', 300, $resolver);
        $second = $cache->remember($user, 'drive', 'shared-drives', 300, $resolver);

        $this->assertSame(['value' => 'cached'], $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $resolved);
    }

    /**
     * 更新操作後に対象サービスだけを新しい世代へ切り替えることを確認する。
     *
     * 前提: DriveとCalendarのキャッシュを保存する。
     * 処理: Drive APIの更新成功として無効化処理を実行する。
     * 期待結果: Driveだけが未取得になり、Calendarの値は維持される。
     */
    public function test_mutation_invalidator_clears_only_target_service(): void
    {
        $user = $this->user(11);
        $cache = app(GoogleWorkspaceCache::class);
        $invalidator = app(GoogleWorkspaceCacheInvalidator::class);
        $cache->put($user, 'drive', 'files', 300, ['drive']);
        $cache->put($user, 'calendar', 'events', 300, ['calendar']);

        $invalidator->invalidateAfterMutation(
            $user,
            'PATCH',
            'https://www.googleapis.com/drive/v3/files/file001',
        );

        $this->assertNull($cache->get($user, 'drive', 'files'));
        $this->assertSame(['calendar'], $cache->get($user, 'calendar', 'events'));
    }

    /**
     * GETリクエストでは表示キャッシュを破棄しないことを確認する。
     *
     * 前提: Chatのスペース一覧をキャッシュする。
     * 処理: Chat APIのGET成功として無効化判定を実行する。
     * 期待結果: 保存済みスペース一覧が維持される。
     */
    public function test_read_request_does_not_invalidate_cache(): void
    {
        $user = $this->user(12);
        $cache = app(GoogleWorkspaceCache::class);
        $invalidator = app(GoogleWorkspaceCacheInvalidator::class);
        $cache->put($user, 'chat', 'spaces', 300, ['spaces']);

        $invalidator->invalidateAfterMutation(
            $user,
            'GET',
            'https://chat.googleapis.com/v1/spaces',
        );

        $this->assertSame(['spaces'], $cache->get($user, 'chat', 'spaces'));
    }

    /**
     * 同じリクエストスコープではキャッシュServiceを共有することを確認する。
     *
     * 前提: ServiceProviderがGoogleWorkspaceCacheをscoped登録している。
     * 処理: コンテナから同じServiceを2回解決する。
     * 期待結果: 同一インスタンスが返り、1リクエスト内のプロセス内メモを再利用できる。
     */
    public function test_cache_service_is_shared_in_application_container(): void
    {
        $first = app(GoogleWorkspaceCache::class);
        $second = app(GoogleWorkspaceCache::class);

        $this->assertSame($first, $second);
    }

    /**
     * 別インスタンスからも永続キャッシュへ保存した値を取得できることを確認する。
     *
     * 前提: 1つ目のServiceから共有ドライブ一覧を保存する。
     * 処理: 新しく生成したServiceから同じキーを取得する。
     * 期待結果: プロセス内メモに依存せず、キャッシュストア経由で同じ値が返る。
     */
    public function test_cached_value_is_available_to_another_service_instance(): void
    {
        $user = $this->user(13);
        $first = new GoogleWorkspaceCache;
        $second = new GoogleWorkspaceCache;
        $first->put($user, 'drive', 'shared-drives', 300, ['shared']);

        $this->assertSame(['shared'], $second->get($user, 'drive', 'shared-drives'));
    }

    /**
     * キャッシュ構造変更前のキーが残っていても新しい画面データとして再利用しないことを確認する。
     *
     * 前提: スキーマ番号を持たない旧形式キーへChatスペース一覧を保存する。
     * 処理: 現行キャッシュServiceから同じリソースをrememberする。
     * 期待結果: 旧値を読まずresolverが実行され、現行スキーマの値が返る。
     */
    public function test_legacy_cache_key_is_ignored_after_schema_change(): void
    {
        $user = $this->user(14);
        $cache = app(GoogleWorkspaceCache::class);
        $legacyKey = sprintf(
            'google-workspace:chat:user:%d:v1:%s',
            $user->id,
            sha1('spaces'),
        );
        Cache::put($legacyKey, ['legacy'], 300);
        $resolved = 0;

        $value = $cache->remember(
            $user,
            'chat',
            'spaces',
            120,
            static function () use (&$resolved): array {
                $resolved++;

                return ['fresh'];
            },
        );

        $this->assertSame(['fresh'], $value);
        $this->assertSame(1, $resolved);
    }

    /**
     * 削除済みクラス由来の不完全オブジェクトをキャッシュから返さないことを確認する。
     *
     * 前提: PHPが`__PHP_Incomplete_Class`として復元した旧DTO相当の値を保存する。
     * 処理: 同じキーをGoogle Workspaceキャッシュから取得する。
     * 期待結果: 不正な値は破棄され、API再取得を促すnullが返る。
     */
    public function test_incomplete_serialized_value_is_discarded(): void
    {
        $user = $this->user(15);
        $cache = app(GoogleWorkspaceCache::class);
        $incomplete = unserialize(
            'O:8:"stdClass":0:{}',
            ['allowed_classes' => false],
        );

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $incomplete);

        $cache->put($user, 'chat', 'spaces', 120, $incomplete);

        $this->assertNull($cache->get($user, 'chat', 'spaces'));
    }

    /**
     * Collection内部に旧DTOが混在する場合も不正キャッシュとして破棄することを確認する。
     *
     * 前提: 不完全オブジェクトを含むCollectionをChatスペース一覧として保存する。
     * 処理: 同じキーをGoogle Workspaceキャッシュから取得する。
     * 期待結果: Collection自体が復元できても内部要素を検査し、nullとして扱う。
     */
    public function test_collection_containing_incomplete_object_is_discarded(): void
    {
        $user = $this->user(16);
        $cache = app(GoogleWorkspaceCache::class);
        $incomplete = unserialize(
            'O:8:"stdClass":0:{}',
            ['allowed_classes' => false],
        );

        $cache->put($user, 'chat', 'spaces', 120, collect([$incomplete]));

        $this->assertNull($cache->get($user, 'chat', 'spaces'));
    }

    /**
     * fresh期限後もstale猶予内なら値を即時返すことを確認する。
     *
     * 前提: fresh TTLを1秒にしたDrive値を保存する。
     * 処理: fresh期限を過ぎてから同じ値を取得する。
     * 期待結果: stale値が返り、servedStaleがtrueになる。
     */
    public function test_expired_fresh_value_is_served_during_stale_grace(): void
    {
        $user = $this->user(17);
        $cache = app(GoogleWorkspaceCache::class);
        $cache->put($user, 'drive', 'files', 1, ['stale']);
        usleep(1100000);

        $this->assertSame(['stale'], $cache->get($user, 'drive', 'files'));
        $this->assertTrue($cache->servedStale());
    }

    /**
     * SWR再検証ではstale値を返さずresolverへ進むことを確認する。
     *
     * 前提: fresh期限切れのCalendar値を保存する。
     * 処理: requireFresh後にrememberを実行する。
     * 期待結果: stale値ではなくresolverの最新版が保存・返却される。
     */
    public function test_require_fresh_bypasses_stale_value(): void
    {
        $user = $this->user(18);
        $cache = app(GoogleWorkspaceCache::class);
        $cache->put($user, 'calendar', 'events', 1, ['old']);
        usleep(1100000);
        $cache->requireFresh();
        $resolved = 0;

        $value = $cache->remember($user, 'calendar', 'events', 120, static function () use (&$resolved): array {
            $resolved++;

            return ['fresh'];
        });

        $this->assertSame(['fresh'], $value);
        $this->assertSame(1, $resolved);
        $this->assertFalse($cache->servedStale());
    }

    /**
     * DBを使わないUnit Test向けに識別子だけを持つ利用者を生成する。
     *
     * @param  int  $id  キャッシュキーへ使用する利用者ID
     * @return User テスト用利用者
     */
    private function user(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }
}
