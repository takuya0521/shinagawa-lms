<?php

namespace App\Services\GoogleDrive\Support;

use App\Data\GoogleDriveComment;
use App\Data\GoogleDriveFile;
use App\Data\GoogleDrivePermission;
use App\Data\GoogleDriveRevision;
use App\Data\GoogleSharedDrive;
use App\Exceptions\GoogleDrive\GoogleDriveResponseException;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Google Drive API固有の配列を画面用DTOへ変換する。
 */
final class GoogleDriveMapper
{
    /**
     * Google Drive API応答を画面表示用ファイル情報へ変換する。
     *
     * @param  array<array-key, mixed>  $file  Google Drive APIのファイル応答
     * @return GoogleDriveFile 画面表示に使用するファイル情報
     */
    public function file(array $file): GoogleDriveFile
    {
        $capabilities = is_array($file['capabilities'] ?? null) ? $file['capabilities'] : [];
        $parents = is_array($file['parents'] ?? null)
            ? array_values(array_filter(
                $file['parents'],
                static fn (mixed $parent): bool => is_string($parent) && $parent !== '',
            ))
            : [];
        $size = $file['size'] ?? null;

        return new GoogleDriveFile(
            id: $this->requiredString($file, 'id'),
            name: $this->requiredString($file, 'name'),
            mimeType: $this->requiredString($file, 'mimeType'),
            modifiedAt: $this->nullableDate($file['modifiedTime'] ?? null),
            webViewLink: $this->optionalString($file, 'webViewLink'),
            webContentLink: $this->optionalString($file, 'webContentLink'),
            iconLink: $this->optionalString($file, 'iconLink'),
            thumbnailLink: $this->optionalString($file, 'thumbnailLink'),
            size: is_numeric($size) ? (int) $size : null,
            description: $this->optionalString($file, 'description'),
            parents: $parents,
            starred: (bool) ($file['starred'] ?? false),
            trashed: (bool) ($file['trashed'] ?? false),
            ownedByMe: (bool) ($file['ownedByMe'] ?? false),
            canEdit: (bool) ($capabilities['canEdit'] ?? false),
            canDelete: (bool) ($capabilities['canDelete'] ?? false),
            canShare: (bool) ($capabilities['canShare'] ?? false),
            canDownload: (bool) ($capabilities['canDownload'] ?? false),
        );
    }

    /**
     * Google Drive API応答を共有ドライブDTOへ変換する。
     *
     * @param  array<array-key, mixed>  $drive  Google Drive APIの共有ドライブ応答
     * @return GoogleSharedDrive 画面表示に使用する共有ドライブ情報
     */
    public function sharedDrive(array $drive): GoogleSharedDrive
    {
        return new GoogleSharedDrive(
            id: $this->requiredString($drive, 'id'),
            name: $this->requiredString($drive, 'name'),
        );
    }

    /**
     * Google Drive API応答を共有権限DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $permission  Google Drive APIの共有権限応答
     * @return GoogleDrivePermission 画面表示に使用する共有権限情報
     */
    public function permission(array $permission): GoogleDrivePermission
    {
        return new GoogleDrivePermission(
            id: $this->requiredString($permission, 'id'),
            type: $this->requiredString($permission, 'type'),
            role: $this->requiredString($permission, 'role'),
            displayName: $this->optionalString($permission, 'displayName'),
            emailAddress: $this->optionalString($permission, 'emailAddress'),
            pendingOwner: (bool) ($permission['pendingOwner'] ?? false),
        );
    }

    /**
     * Google Drive API応答をコメントDTOへ変換する。
     *
     * @param  array<array-key, mixed>  $comment  Google Drive APIのコメント応答
     * @return GoogleDriveComment 画面表示に使用するコメント情報
     */
    public function comment(array $comment): GoogleDriveComment
    {
        $replies = is_array($comment['replies'] ?? null) ? $comment['replies'] : [];

        return new GoogleDriveComment(
            id: $this->requiredString($comment, 'id'),
            content: $this->requiredString($comment, 'content'),
            authorName: $this->nestedString($comment, ['author', 'displayName']) ?? 'Googleユーザー',
            createdAt: $this->requiredDate($comment['createdTime'] ?? null),
            resolved: (bool) ($comment['resolved'] ?? false),
            replies: collect($replies)
                ->filter(static fn (mixed $reply): bool => is_array($reply))
                ->map(fn (array $reply): array => [
                    'id' => $this->requiredString($reply, 'id'),
                    'content' => $this->requiredString($reply, 'content'),
                    'author' => $this->nestedString($reply, ['author', 'displayName']) ?? 'Googleユーザー',
                    'created_at' => $this->requiredDate($reply['createdTime'] ?? null),
                ])
                ->values(),
        );
    }

    /**
     * Google Drive API応答を版履歴DTOへ変換する。
     *
     * @param  array<array-key, mixed>  $revision  Google Drive APIの版履歴応答
     * @return GoogleDriveRevision 画面表示に使用する版履歴情報
     */
    public function revision(array $revision): GoogleDriveRevision
    {
        return new GoogleDriveRevision(
            id: $this->requiredString($revision, 'id'),
            modifiedAt: $this->nullableDate($revision['modifiedTime'] ?? null),
            modifierName: $this->nestedString($revision, ['lastModifyingUser', 'displayName']),
            size: is_numeric($revision['size'] ?? null) ? (int) $revision['size'] : null,
            keepForever: (bool) ($revision['keepForever'] ?? false),
        );
    }

    /**
     * API応答から必須文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  Google Drive API応答
     * @param  string  $key  取得する必須項目名
     * @return string 指定キーに対応する必須文字列
     *
     * @throws GoogleDriveResponseException 必須項目が存在しない場合
     */
    private function requiredString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new GoogleDriveResponseException("Google Drive API応答に{$key}がありません。");
        }

        return $value;
    }

    /**
     * API応答から任意文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  Google Drive API応答
     * @param  string  $key  取得する任意項目名
     * @return ?string 指定キーに対応する文字列。値がない場合はnull
     */
    private function optionalString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * API応答の入れ子項目から任意文字列を取得する。
     *
     * @param  array<array-key, mixed>  $values  Google Drive API応答
     * @param  list<string>  $keys  上位階層から順に並べた項目名
     * @return ?string 指定した階層にある文字列。値がない場合はnull
     */
    private function nestedString(array $values, array $keys): ?string
    {
        $current = $values;

        foreach ($keys as $index => $key) {
            $value = $current[$key] ?? null;

            if ($index === array_key_last($keys)) {
                return is_string($value) && $value !== '' ? $value : null;
            }

            if (! is_array($value)) {
                return null;
            }

            $current = $value;
        }

        return null;
    }

    /**
     * API応答から必須日時を取得する。
     *
     * @param  mixed  $value  変換対象の値
     * @return CarbonImmutable アプリケーションのタイムゾーンへ変換した必須日時
     *
     * @throws GoogleDriveResponseException 日時が未設定または解析できない場合
     */
    private function requiredDate(mixed $value): CarbonImmutable
    {
        $date = $this->nullableDate($value);

        if (! $date instanceof CarbonImmutable) {
            throw new GoogleDriveResponseException('Google Drive API応答に日時がありません。');
        }

        return $date;
    }

    /**
     * API応答から任意日時を取得する。
     *
     * @param  mixed  $value  変換対象の値
     * @return ?CarbonImmutable アプリケーションのタイムゾーンへ変換した日時。値がない場合はnull
     *
     * @throws GoogleDriveResponseException 日時を解析できない場合
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
            throw new GoogleDriveResponseException(
                'Google Drive APIの日時を解析できません。',
                previous: $exception,
            );
        }
    }
}
