<?php

namespace App\Http\Requests\Announcement;

use App\Enums\AnnouncementNoticeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 利用者向けお知らせ一覧の検索条件を検証する。
 */
final class VisibleAnnouncementIndexRequest extends FormRequest
{
    /**
     * 認証済み利用者向けルートのため常に許可する。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 利用者向けお知らせ一覧の検索条件に適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 検索条件の入力規則
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'notice_type' => ['nullable', Rule::enum(AnnouncementNoticeType::class)],
            'important_only' => ['nullable', 'boolean'],
        ];
    }

    /**
     * キーワードを前後空白を除去して返す。
     *
     * @return string 検索キーワード。未指定の場合は空文字
     */
    public function keyword(): string
    {
        return trim((string) $this->validated('keyword', ''));
    }

    /**
     * 選択されたお知らせ種別を返す。
     *
     * @return AnnouncementNoticeType|null お知らせ種別
     */
    public function noticeType(): ?AnnouncementNoticeType
    {
        return AnnouncementNoticeType::tryFrom(
            (string) $this->validated('notice_type', ''),
        );
    }

    /**
     * 重要なお知らせだけを表示する指定を返す。
     *
     * @return bool|null 指定ありの場合は真偽値、未指定の場合はnull
     */
    public function importantOnly(): ?bool
    {
        return $this->has('important_only')
            ? $this->boolean('important_only')
            : null;
    }
}
