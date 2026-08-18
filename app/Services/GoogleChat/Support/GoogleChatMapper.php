<?php

namespace App\Services\GoogleChat\Support;

use App\Data\GoogleChatMembership;
use App\Data\GoogleChatMessage;
use App\Data\GoogleChatReaction;
use App\Data\GoogleChatSpace;
use App\Exceptions\GoogleChat\GoogleChatResponseException;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Google Chat APIの可変配列を画面用DTOへ変換する。
 * API応答の型検証と既定値を一箇所へ集約し、取得・更新Serviceが通信処理だけへ
 * 集中できるようにする。
 */
final class GoogleChatMapper
{
    /**
     * API応答内のリソース名を同じ検証規則で扱うため、リソース検証Serviceを注入する。
     *
     * @param  GoogleChatResource  $resource  Googleリソース名の検証担当
     */
    public function __construct(
        private readonly GoogleChatResource $resource,
    ) {}

    /**
     * Google Chat APIのスペース情報を画面用DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $space  Google Chat API応答
     * @return GoogleChatSpace 画面表示用スペース情報
     *
     * @throws GoogleChatResponseException 必須項目が存在しない場合
     */
    public function space(array $space): GoogleChatSpace
    {
        $resourceName = $this->requiredString($space, 'name');
        $spaceDetails = is_array($space['spaceDetails'] ?? null) ? $space['spaceDetails'] : [];
        $permissionSettings = is_array($space['permissionSettings'] ?? null)
            ? $space['permissionSettings']
            : [];

        return new GoogleChatSpace(
            id: $this->resource->idFromName($resourceName, 'spaces/'),
            resourceName: $resourceName,
            displayName: $this->optionalString($space, 'displayName') ?? '名称未設定のチャット',
            type: $this->optionalString($space, 'spaceType') ?? 'SPACE_TYPE_UNSPECIFIED',
            spaceUri: $this->optionalString($space, 'spaceUri'),
            description: $this->optionalString($spaceDetails, 'description'),
            historyState: $this->optionalString($space, 'spaceHistoryState') ?? 'HISTORY_STATE_UNSPECIFIED',
            membershipState: $this->optionalString($space, 'membershipState'),
            canManageMembers: $permissionSettings !== [],
        );
    }

    /**
     * Google Chat APIのメンバーシップ情報を画面用DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $membership  Google Chat API応答
     * @return GoogleChatMembership 画面表示用メンバー情報
     *
     * @throws GoogleChatResponseException 必須項目が存在しない場合
     */
    public function membership(array $membership): GoogleChatMembership
    {
        $resourceName = $this->requiredString($membership, 'name');
        $member = is_array($membership['member'] ?? null) ? $membership['member'] : [];

        return new GoogleChatMembership(
            id: $this->resource->idFromName($resourceName, '/members/'),
            resourceName: $resourceName,
            memberName: $this->requiredString($member, 'name'),
            displayName: $this->optionalString($member, 'displayName') ?? '名称未設定',
            email: $this->optionalString($member, 'email'),
            state: $this->optionalString($membership, 'state') ?? 'MEMBER_STATE_UNSPECIFIED',
            role: $this->optionalString($membership, 'role') ?? 'ROLE_MEMBER',
            type: $this->optionalString($member, 'type') ?? 'TYPE_UNSPECIFIED',
        );
    }

    /**
     * Google Chat APIのメッセージ情報を画面用DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $message  Google Chat API応答
     * @return GoogleChatMessage 画面表示用メッセージ
     *
     * @throws GoogleChatResponseException 必須項目または日時が不正な場合
     */
    public function message(array $message): GoogleChatMessage
    {
        $resourceName = $this->requiredString($message, 'name');
        $sender = is_array($message['sender'] ?? null) ? $message['sender'] : [];
        $thread = is_array($message['thread'] ?? null) ? $message['thread'] : [];
        $reactionSummaries = is_array($message['emojiReactionSummaries'] ?? null)
            ? $message['emojiReactionSummaries']
            : [];
        $attachments = is_array($message['attachment'] ?? null) ? $message['attachment'] : [];

        return new GoogleChatMessage(
            id: $this->resource->idFromName($resourceName, '/messages/'),
            resourceName: $resourceName,
            senderName: $this->optionalString($sender, 'displayName') ?? 'Google Chat利用者',
            senderResourceName: $this->optionalString($sender, 'name'),
            text: $this->optionalString($message, 'text') ?? '',
            createdAt: $this->requiredDate($message['createTime'] ?? null),
            updatedAt: $this->nullableDate($message['lastUpdateTime'] ?? null),
            threadName: $this->optionalString($thread, 'name'),
            reactions: collect($reactionSummaries)
                ->filter(static fn (mixed $reaction): bool => is_array($reaction))
                ->map(fn (array $reaction): GoogleChatReaction => $this->reactionSummary($reaction))
                ->values(),
            attachments: array_values(array_filter(array_map(
                fn (mixed $attachment): ?array => is_array($attachment)
                    ? $this->attachment($attachment)
                    : null,
                $attachments,
            ))),
            deleted: isset($message['deleteTime']),
        );
    }

    /**
     * 個別リアクションAPI応答を画面表示用DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $reaction  API応答
     * @return GoogleChatReaction 画面表示用リアクション
     *
     * @throws GoogleChatResponseException 必須項目が存在しない場合
     */
    public function reaction(array $reaction): GoogleChatReaction
    {
        $emoji = is_array($reaction['emoji'] ?? null) ? $reaction['emoji'] : [];
        $user = is_array($reaction['user'] ?? null) ? $reaction['user'] : [];
        $customEmoji = is_array($emoji['customEmoji'] ?? null) ? $emoji['customEmoji'] : [];

        return new GoogleChatReaction(
            resourceName: $this->requiredString($reaction, 'name'),
            emoji: $this->optionalString($emoji, 'unicode')
                ?? $this->optionalString($customEmoji, 'uid')
                ?? 'リアクション',
            userName: $this->optionalString($user, 'displayName')
                ?? $this->optionalString($user, 'name'),
        );
    }

    /**
     * API応答の任意文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  対象配列
     * @param  string  $key  配列キー
     * @return string|null 任意文字列。未設定時はnull
     */
    public function optionalString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * リアクション集計を画面表示用DTOへ変換する。
     * 集計APIでは個別リアクション名が返らないため、削除操作用IDは空文字として扱う。
     *
     * @param  array<array-key, mixed>  $reaction  API応答
     * @return GoogleChatReaction 画面表示用リアクション
     */
    private function reactionSummary(array $reaction): GoogleChatReaction
    {
        $emoji = is_array($reaction['emoji'] ?? null) ? $reaction['emoji'] : [];
        $unicode = $this->optionalString($emoji, 'unicode');
        $customEmoji = is_array($emoji['customEmoji'] ?? null) ? $emoji['customEmoji'] : [];
        $customName = $this->optionalString($customEmoji, 'uid');
        $count = is_numeric($reaction['reactionCount'] ?? null) ? (int) $reaction['reactionCount'] : 1;

        return new GoogleChatReaction(
            resourceName: '',
            emoji: ($unicode ?? $customName ?? 'リアクション').' × '.$count,
            userName: null,
        );
    }

    /**
     * Chat添付情報を画面表示用の安全な配列へ変換する。
     *
     * @param  array<array-key, mixed>  $attachment  API応答
     * @return array{name: string, content_name: string, content_type: string, download_uri: string|null} 添付情報
     */
    private function attachment(array $attachment): array
    {
        return [
            'name' => $this->optionalString($attachment, 'name') ?? '',
            'content_name' => $this->optionalString($attachment, 'contentName') ?? '添付ファイル',
            'content_type' => $this->optionalString($attachment, 'contentType') ?? 'application/octet-stream',
            'download_uri' => $this->optionalString($attachment, 'downloadUri'),
        ];
    }

    /**
     * API応答の必須文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  対象配列
     * @param  string  $key  配列キー
     * @return string 指定した必須項目の文字列値
     *
     * @throws GoogleChatResponseException 必須文字列が存在しない場合
     */
    private function requiredString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new GoogleChatResponseException("Google Chat API応答に{$key}がありません。");
        }

        return $value;
    }

    /**
     * 必須日時をアプリケーションのタイムゾーンへ変換する。
     *
     * @param  mixed  $value  API応答日時
     * @return CarbonImmutable LMS表示用に正規化した日時
     *
     * @throws GoogleChatResponseException 日時形式が不正な場合
     */
    private function requiredDate(mixed $value): CarbonImmutable
    {
        $date = $this->nullableDate($value);

        if (! $date instanceof CarbonImmutable) {
            throw new GoogleChatResponseException('Google Chat API応答に送信日時がありません。');
        }

        return $date;
    }

    /**
     * 任意日時をアプリケーションのタイムゾーンへ変換する。
     *
     * @param  mixed  $value  API応答日時
     * @return CarbonImmutable|null 変換済み日時。未設定時はnull
     *
     * @throws GoogleChatResponseException 日時形式が不正な場合
     */
    private function nullableDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)
                ->setTimezone((string) config('app.timezone'));
        } catch (InvalidFormatException $exception) {
            throw new GoogleChatResponseException(
                'Google Chat APIの日時を解析できません。',
                previous: $exception,
            );
        }
    }
}
