<?php

namespace App\Http\Requests\Admin;

use App\Data\Admin\AnnouncementIndexFilters;
use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * 管理者向けお知らせ一覧の検索条件を検証する。
 */
final class AnnouncementIndexRequest extends FormRequest
{
    /**
     * 管理者ルートのミドルウェアで認証・認可を行うため常に許可する。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * お知らせ一覧の検索条件に適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 検索条件の入力規則
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'notice_type' => ['nullable', Rule::enum(AnnouncementNoticeType::class)],
            'target' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(AnnouncementStatus::class)],
            'important_only' => ['nullable', 'boolean'],
            'publish_from' => ['nullable', 'date_format:Y-m-d'],
            'publish_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * 掲載期間の開始日と終了日の前後関係を追加検証する。
     *
     * @return list<callable(Validator): void> 追加検証処理一覧
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $publishFrom = $this->input('publish_from');
                $publishTo = $this->input('publish_to');

                if (
                    is_string($publishFrom)
                    && $publishFrom !== ''
                    && is_string($publishTo)
                    && $publishTo !== ''
                    && $publishTo < $publishFrom
                ) {
                    $validator->errors()->add(
                        'publish_to',
                        '掲載期間の終了日には、開始日以降の日付を指定してください。',
                    );
                }
            },
        ];
    }

    /**
     * 検証済み入力から型付き検索条件を生成する。
     *
     * @return AnnouncementIndexFilters お知らせ一覧の検索条件
     */
    public function filters(): AnnouncementIndexFilters
    {
        return new AnnouncementIndexFilters(
            keyword: $this->keyword(),
            noticeType: $this->noticeType(),
            target: $this->target(),
            status: $this->status(),
            importantOnly: $this->importantOnly(),
            publishFrom: $this->publishFrom(),
            publishTo: $this->publishTo(),
        );
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
     * 選択された公開対象を返す。
     *
     * @return string|null 公開対象を表す「種別:値」形式の文字列
     */
    public function target(): ?string
    {
        $value = trim((string) $this->validated('target', ''));

        return $value !== ''
            ? $value
            : null;
    }

    /**
     * 選択された公開状態を返す。
     *
     * @return AnnouncementStatus|null 公開状態
     */
    public function status(): ?AnnouncementStatus
    {
        return AnnouncementStatus::tryFrom(
            (string) $this->validated('status', ''),
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

    /**
     * 掲載期間の検索開始日を返す。
     *
     * @return string|null Y-m-d形式の開始日
     */
    public function publishFrom(): ?string
    {
        $value = $this->validated('publish_from');

        return is_string($value)
            ? $value
            : null;
    }

    /**
     * 掲載期間の検索終了日を返す。
     *
     * @return string|null Y-m-d形式の終了日
     */
    public function publishTo(): ?string
    {
        $value = $this->validated('publish_to');

        return is_string($value)
            ? $value
            : null;
    }
}
