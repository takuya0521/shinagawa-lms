<?php

namespace App\Services\GoogleWorkspace\Support;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Google Workspace画面のSWRキャッシュとサービス単位の無効化を管理する。
 *
 * fresh期間を過ぎた値も短いstale期間だけ保持し、画面には即時返却する。
 * stale値を返したレスポンスはブラウザ側で検知し、Google APIから最新版を
 * バックグラウンド再取得することで、外部APIの待ち時間を体感遅延から切り離す。
 */
final class GoogleWorkspaceCache
{
    // SWRエンベロープ形式と互換性のない旧キャッシュ値をキー空間から分離する。
    private const CACHE_SCHEMA_VERSION = 3;

    private const VERSION_TTL_SECONDS = 604800;

    private const MIN_STALE_GRACE_SECONDS = 300;

    private const MAX_STALE_GRACE_SECONDS = 1800;

    /** @var array<string, int> */
    private array $versions = [];

    /**
     * 同一リクエスト内で再利用するSWR値。
     *
     * @var array<string, array{
     *     fresh_until: float,
     *     stale_until: float,
     *     value: object|array<array-key, mixed>|string|int|float|bool|null
     * }>
     */
    private array $memory = [];

    /** stale値を返さずGoogle APIから最新版を取得させる場合はtrue。 */
    private bool $freshOnly = false;

    /** このリクエスト中にstale値を画面へ返した場合はtrue。 */
    private bool $servedStale = false;

    /** @return Repository アプリケーション既定のキャッシュストア */
    private function store(): Repository
    {
        return Cache::store();
    }

    /**
     * 利用者・サービス・検索条件ごとのGoogle API結果をSWR方式で再利用する。
     *
     * @template TValue of object|array|string|int|float|bool
     *
     * @param  User  $user  キャッシュを分離するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     * @param  string  $resourceKey  表示条件を一意に表す文字列
     * @param  int  $ttlSeconds  最新値として扱う秒数
     * @param  Closure(): TValue  $resolver  キャッシュがない場合にAPI結果を取得する処理
     * @return TValue キャッシュ済みまたは新規取得した画面表示データ
     */
    public function remember(
        User $user,
        string $service,
        string $resourceKey,
        int $ttlSeconds,
        Closure $resolver,
    ): mixed {
        $cached = $this->get($user, $service, $resourceKey);

        if ($cached !== null) {
            /** @var TValue $cached */
            return $cached;
        }

        $value = $resolver();
        $this->put($user, $service, $resourceKey, $ttlSeconds, $value);

        return $value;
    }

    /**
     * 保存済み値を返す。fresh期間超過後もstale猶予内なら即時表示へ利用する。
     *
     * @param  User  $user  キャッシュを分離するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     * @param  string  $resourceKey  表示条件を一意に表す文字列
     * @return object|array<array-key, mixed>|string|int|float|bool|null 保存済み値。利用不可ならnull
     */
    public function get(User $user, string $service, string $resourceKey): object|array|string|int|float|bool|null
    {
        $key = $this->dataKey($user, $service, $resourceKey);
        $entry = $this->memory[$key] ?? null;

        if ($entry === null) {
            $stored = $this->store()->get($key);
            $entry = $this->normalizeEntry($stored);

            if ($entry === null) {
                if ($stored !== null) {
                    // DTO変更前など復元不能な値は500にせず破棄し、API再取得へ切り替える。
                    $this->discardDataEntry($key);
                }

                return null;
            }

            $this->memory[$key] = $entry;
        }

        $now = microtime(true);

        if ($entry['stale_until'] <= $now) {
            $this->discardDataEntry($key);

            return null;
        }

        if (! $this->isCacheValueUsable($entry['value'])) {
            $this->discardDataEntry($key);

            return null;
        }

        if ($entry['fresh_until'] <= $now) {
            if ($this->freshOnly) {
                return null;
            }

            $this->servedStale = true;
        }

        return $entry['value'];
    }

    /**
     * Google API結果をfresh期間とstale猶予を持つSWRエンベロープとして保存する。
     *
     * @param  User  $user  キャッシュを分離するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     * @param  string  $resourceKey  表示条件を一意に表す文字列
     * @param  int  $ttlSeconds  最新値として扱う秒数
     * @param  object|array<array-key, mixed>|string|int|float|bool|null  $value  保存する画面表示データ
     */
    public function put(
        User $user,
        string $service,
        string $resourceKey,
        int $ttlSeconds,
        object|array|string|int|float|bool|null $value,
    ): void {
        $freshTtl = max(1, $ttlSeconds);
        $staleGrace = $this->staleGraceSeconds($freshTtl);
        $now = microtime(true);
        $key = $this->dataKey($user, $service, $resourceKey);
        $entry = [
            'fresh_until' => $now + $freshTtl,
            'stale_until' => $now + $freshTtl + $staleGrace,
            'value' => $value,
        ];

        $this->memory[$key] = $entry;
        $this->store()->put($key, $entry, $freshTtl + $staleGrace);
    }

    /** stale値を使わず、期限切れ項目だけGoogle APIから再取得するモードへ切り替える。 */
    public function requireFresh(): void
    {
        $this->freshOnly = true;
        $this->servedStale = false;
    }

    /** @return bool このリクエストでstale値を利用者へ返した場合はtrue */
    public function servedStale(): bool
    {
        return $this->servedStale;
    }

    /**
     * 指定サービスの世代番号を進め、更新前のAPI結果を参照対象から外す。
     *
     * @param  User  $user  キャッシュを無効化するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     */
    public function invalidate(User $user, string $service): void
    {
        $key = $this->versionKey($user, $service);
        $nextVersion = $this->version($user, $service) + 1;

        $this->store()->put($key, $nextVersion, self::VERSION_TTL_SECONDS);
        $this->versions[$key] = $nextVersion;
        $this->forgetMemoryFor($user, $service);
        $this->servedStale = false;
    }

    /**
     * OAuth再連携・解除後にGoogle Workspace各サービスの全キャッシュを無効化する。
     *
     * @param  User  $user  キャッシュを無効化するLMS利用者
     */
    public function invalidateAll(User $user): void
    {
        foreach (['drive', 'classroom', 'calendar', 'chat', 'meet', 'forms'] as $service) {
            $this->invalidate($user, $service);
        }
    }

    /**
     * 現在のキャッシュ世代番号を返す。
     *
     * @param  User  $user  キャッシュを分離するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     * @return int 現在のキャッシュ世代番号
     */
    private function version(User $user, string $service): int
    {
        $key = $this->versionKey($user, $service);

        if (isset($this->versions[$key])) {
            return $this->versions[$key];
        }

        $version = max(1, (int) $this->store()->get($key, 1));
        $this->versions[$key] = $version;

        return $version;
    }

    /**
     * API結果を保存する衝突しないキーを生成する。
     *
     * @param  User  $user  キャッシュを分離するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     * @param  string  $resourceKey  表示条件を一意に表す文字列
     * @return string API結果を保存するキー
     */
    private function dataKey(User $user, string $service, string $resourceKey): string
    {
        return sprintf(
            'google-workspace:s%d:%s:user:%d:v%d:%s',
            self::CACHE_SCHEMA_VERSION,
            $service,
            $user->id,
            $this->version($user, $service),
            sha1($resourceKey),
        );
    }

    /**
     * サービス単位の世代番号を保存するキーを生成する。
     *
     * @param  User  $user  キャッシュを分離するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     * @return string キャッシュ世代番号の保存キー
     */
    private function versionKey(User $user, string $service): string
    {
        return sprintf('google-workspace:s%d:%s:user:%d:version', self::CACHE_SCHEMA_VERSION, $service, $user->id);
    }

    /**
     * 永続キャッシュからSWRエンベロープを安全に復元する。
     *
     * @param  mixed  $stored  キャッシュストアから復元した値
     * @return array{
     *     fresh_until: float,
     *     stale_until: float,
     *     value: object|array<array-key, mixed>|string|int|float|bool|null
     * }|null
     */
    private function normalizeEntry(mixed $stored): ?array
    {
        if (! is_array($stored)
            || ! is_numeric($stored['fresh_until'] ?? null)
            || ! is_numeric($stored['stale_until'] ?? null)
            || ! array_key_exists('value', $stored)
            || ! $this->isCacheValueUsable($stored['value'])) {
            return null;
        }

        return [
            'fresh_until' => (float) $stored['fresh_until'],
            'stale_until' => (float) $stored['stale_until'],
            'value' => $stored['value'],
        ];
    }

    /**
     * fresh期間後に古い値を即表示へ利用できる猶予秒数を決定する。
     *
     * @param  int  $freshTtl  最新値として扱う秒数
     * @return int stale表示を許可する追加秒数
     */
    private function staleGraceSeconds(int $freshTtl): int
    {
        return max(
            self::MIN_STALE_GRACE_SECONDS,
            min(self::MAX_STALE_GRACE_SECONDS, $freshTtl * 3),
        );
    }

    /**
     * 復元値に削除済みクラス由来の不完全オブジェクトが含まれないか確認する。
     *
     * @param  mixed  $value  キャッシュストアまたはプロセス内メモから復元した値
     * @return bool 画面表示データとして安全に再利用できる場合はtrue
     */
    private function isCacheValueUsable(mixed $value): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            return false;
        }

        if ($value === null || is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->isCacheValueUsable($item)) {
                    return false;
                }
            }

            return true;
        }

        if ($value instanceof Collection) {
            foreach ($value as $item) {
                if (! $this->isCacheValueUsable($item)) {
                    return false;
                }
            }

            return true;
        }

        if (is_object($value) && str_starts_with($value::class, 'App\\Data\\')) {
            foreach (get_object_vars($value) as $property) {
                if (! $this->isCacheValueUsable($property)) {
                    return false;
                }
            }

            return true;
        }

        return is_object($value);
    }

    /**
     * 指定データキーをプロセス内メモと永続キャッシュの両方から破棄する。
     *
     * @param  string  $key  破棄対象の完全なキャッシュキー
     */
    private function discardDataEntry(string $key): void
    {
        unset($this->memory[$key]);
        $this->store()->forget($key);
    }

    /**
     * 指定利用者・サービスのプロセス内キャッシュだけを破棄する。
     *
     * @param  User  $user  キャッシュを無効化するLMS利用者
     * @param  string  $service  drive、classroom、calendar、chat、meet、formsのいずれか
     */
    private function forgetMemoryFor(User $user, string $service): void
    {
        $prefix = sprintf('google-workspace:s%d:%s:user:%d:', self::CACHE_SCHEMA_VERSION, $service, $user->id);

        foreach (array_keys($this->memory) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->memory[$key]);
            }
        }
    }
}
