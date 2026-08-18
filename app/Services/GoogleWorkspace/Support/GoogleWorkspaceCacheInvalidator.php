<?php

namespace App\Services\GoogleWorkspace\Support;

use App\Models\User;

/**
 * Google Workspace APIの更新先URLから、破棄すべき画面キャッシュを判定する。
 *
 * 更新系Serviceごとにキャッシュ削除を書き忘れると、操作成功後も古い一覧が表示される。
 * HTTP通信の成功直後へ無効化を集約し、Drive・Classroom・Calendar・Chat・Meet・Formsで同じ整合性を保つ。
 */
final class GoogleWorkspaceCacheInvalidator
{
    /**
     * @param  GoogleWorkspaceCache  $cache  利用者・サービス単位のSWRキャッシュ
     */
    public function __construct(
        private readonly GoogleWorkspaceCache $cache,
    ) {}

    /**
     * 更新系HTTPメソッドが成功した場合だけ、URLに対応するサービスを無効化する。
     *
     * @param  User  $user  Google Workspaceを操作したLMS利用者
     * @param  string  $method  実行したHTTPメソッド
     * @param  string  $url  呼び出したGoogle API URL
     */
    public function invalidateAfterMutation(User $user, string $method, string $url): void
    {
        if (in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $service = $this->serviceFromUrl($url);

        if ($service !== null) {
            $this->cache->invalidate($user, $service);
        }
    }

    /**
     * Google API URLをLMS内のサービス識別子へ変換する。
     *
     * @param  string  $url  Google API URL
     * @return string|null drive、classroom、calendar、chat、meet、formsのいずれか。対象外URLはnull
     */
    private function serviceFromUrl(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        if ($host === 'classroom.googleapis.com') {
            return 'classroom';
        }

        if ($host === 'chat.googleapis.com') {
            return 'chat';
        }

        if ($host === 'meet.googleapis.com') {
            return 'meet';
        }

        if ($host === 'forms.googleapis.com') {
            return 'forms';
        }

        if (str_contains($path, '/calendar/')) {
            return 'calendar';
        }

        if (str_contains($path, '/drive/')) {
            return 'drive';
        }

        return null;
    }
}
