<?php

namespace Tests\Unit\Services\GoogleWorkspace;

use App\Exceptions\GoogleWorkspace\GoogleWorkspaceResponseException;
use App\Models\GoogleDriveConnection;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceScopeService;
use PHPUnit\Framework\TestCase;

/**
 * Google OAuthスコープの分割と不足権限検出を確認する。
 */
final class GoogleWorkspaceScopeServiceTest extends TestCase
{
    /**
     * 空白の種類が混在したスコープ文字列を比較可能な一覧へ変換できることを確認する。
     *
     * 前提: 半角空白、改行、タブが混在するOAuthスコープ文字列を準備する。
     * 処理: `split`を実行する。
     * 期待結果: 空要素を含まないスコープ一覧が元の順序で返る。
     */
    public function test_split_normalizes_whitespace_separated_scopes(): void
    {
        $service = new GoogleWorkspaceScopeService;

        self::assertSame(
            ['scope.drive', 'scope.calendar', 'scope.chat'],
            $service->split(" scope.drive\n\tscope.calendar   scope.chat "),
        );
    }

    /**
     * 保存済み接続情報に必要スコープがすべて含まれる場合だけ利用可能と判定されることを確認する。
     *
     * 前提: DriveとCalendarのスコープを持つ接続情報を準備する。
     * 処理: 必要スコープを変えて`hasScopes`を実行する。
     * 期待結果: 保有済み権限だけならtrue、Chat権限を含めるとfalseが返る。
     */
    public function test_has_scopes_requires_every_requested_scope(): void
    {
        $connection = new GoogleDriveConnection;
        $connection->scope = 'scope.drive scope.calendar';
        $service = new GoogleWorkspaceScopeService;

        self::assertTrue($service->hasScopes($connection, ['scope.drive', 'scope.calendar']));
        self::assertFalse($service->hasScopes($connection, ['scope.drive', 'scope.chat']));
    }

    /**
     * Googleが必要スコープを返さなかった場合に再連携が必要な例外となることを確認する。
     *
     * 前提: Drive権限だけが付与された認証結果を準備する。
     * 処理: Calendar権限も必要として`assertGranted`を実行する。
     * 期待結果: 不足権限を見逃さずGoogleWorkspaceResponseExceptionが送出される。
     */
    public function test_assert_granted_rejects_missing_scope(): void
    {
        $service = new GoogleWorkspaceScopeService;

        $this->expectException(GoogleWorkspaceResponseException::class);
        $service->assertGranted('scope.drive', ['scope.drive', 'scope.calendar']);
    }
}
