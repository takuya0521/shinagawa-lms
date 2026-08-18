<?php

namespace App\Models;

use Database\Factories\GoogleDriveConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ユーザー単位のGoogle Workspace共通OAuth連携情報を保持する。
 *
 * アクセストークンとリフレッシュトークンは暗号化キャストを使用し、DB上へ平文で
 * 保存しない。既存Drive連携との後方互換性を保つためクラス名とテーブル名は維持し、
 * 保存したトークンをGoogle Workspace各連携機能で共有する。
 *
 * @property int $id
 * @property int $user_id
 * @property string $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $token_expires_at
 * @property string $scope
 * @property Carbon|null $last_connected_at
 */
#[Fillable([
    'user_id',
    'access_token',
    'refresh_token',
    'token_expires_at',
    'scope',
    'last_connected_at',
])]
final class GoogleDriveConnection extends Model
{
    /** @use HasFactory<GoogleDriveConnectionFactory> */
    use HasFactory;

    /**
     * トークンの所有者を特定し、他ユーザーのGoogle情報へ混在させないための関連を返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * OAuthトークンを保存時に暗号化し、日時項目をCarbonとして扱うためのキャストを返す。
     *
     * @return array<string, string> モデル属性のキャスト定義
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_connected_at' => 'datetime',
        ];
    }
}
