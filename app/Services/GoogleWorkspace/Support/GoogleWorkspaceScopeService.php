<?php

namespace App\Services\GoogleWorkspace\Support;

use App\Exceptions\GoogleWorkspace\GoogleWorkspaceResponseException;
use App\Models\GoogleDriveConnection;

/**
 * Google OAuthスコープの正規化・比較・付与確認を担当する。
 */
final class GoogleWorkspaceScopeService
{
    /**
     * 保存済みスコープに、指定APIが必要とする権限がすべて含まれるか判定する。
     *
     * @param  GoogleDriveConnection  $connection  保存済みGoogle OAuth連携情報
     * @param  list<string>  $requiredScopes  判定対象スコープ
     * @return bool 必要な権限がすべて含まれる場合はtrue
     */
    public function hasScopes(GoogleDriveConnection $connection, array $requiredScopes): bool
    {
        $grantedScopes = $this->split($connection->scope);

        foreach ($requiredScopes as $requiredScope) {
            if (! in_array($requiredScope, $grantedScopes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Googleから返された権限一覧に必要な操作スコープが含まれることを確認する。
     *
     * @param  string  $grantedScope  Googleが返した空白区切りの権限一覧
     * @param  list<string>  $requiredScopes  LMSが要求したスコープ
     *
     * @throws GoogleWorkspaceResponseException 必要な権限が付与されていない場合
     */
    public function assertGranted(string $grantedScope, array $requiredScopes): void
    {
        $grantedScopes = $this->split($grantedScope);

        foreach ($requiredScopes as $requiredScope) {
            if (! in_array($requiredScope, $grantedScopes, true)) {
                throw new GoogleWorkspaceResponseException(
                    'Google Workspaceの必要な操作権限が付与されていません。',
                );
            }
        }
    }

    /**
     * Googleが返す空白区切りスコープを、比較可能な一覧へ変換する。
     *
     * @param  string  $scope  空白区切りのOAuthスコープ
     * @return list<string> 分割済みスコープ
     */
    public function split(string $scope): array
    {
        $scopes = preg_split('/\s+/u', trim($scope)) ?: [];

        return array_values(array_filter(
            $scopes,
            static fn (string $item): bool => $item !== '',
        ));
    }
}
