<?php

namespace Database\Factories;

use App\Models\GoogleDriveConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Google Workspace連携済み状態を、実在する認証情報を使用せずに再現するFactory。
 *
 * @extends Factory<GoogleDriveConnection>
 */
final class GoogleDriveConnectionFactory extends Factory
{
    /** @var class-string<GoogleDriveConnection> */
    protected $model = GoogleDriveConnection::class;

    /**
     * 有効期限内のOAuthトークンを持つ標準的なテストデータを返す。
     *
     * @return array<string, mixed> モデル作成時に使用する属性値
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'access_token' => 'test-access-'.Str::random(32),
            'refresh_token' => 'test-refresh-'.Str::random(32),
            'token_expires_at' => now()->addHour(),
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/drive',
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/chat.spaces',
                'https://www.googleapis.com/auth/chat.memberships',
                'https://www.googleapis.com/auth/chat.messages',
                'https://www.googleapis.com/auth/chat.messages.reactions',
                'https://www.googleapis.com/auth/chat.delete',
            ]),
            'last_connected_at' => now(),
        ];
    }

    /**
     * アクセストークン更新処理を検証できるよう、期限切れ状態へ変更する。
     *
     * @return static 期限切れ状態を追加したFactory
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'token_expires_at' => now()->subMinute(),
        ]);
    }
}
