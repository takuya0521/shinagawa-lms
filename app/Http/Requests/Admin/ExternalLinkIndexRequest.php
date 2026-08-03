<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ExternalLinkIndexRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力値へ適用する検証規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'link_type' => ['nullable', Rule::enum(ExternalLinkType::class)],
            'scope_type' => ['nullable', Rule::enum(ExternalLinkScopeType::class)],
            'status' => ['nullable', Rule::enum(MasterStatus::class)],
        ];
    }

    /**
     * 検索キーワードを取得する。
     *
     * @return string 取得した文字列
     */
    public function keyword(): string
    {
        return trim((string) $this->validated('keyword', ''));
    }

    /**
     * 外部リンク種別を取得する。
     *
     * @return ?ExternalLinkType 処理結果。取得できない場合はnull
     */
    public function linkType(): ?ExternalLinkType
    {
        return ExternalLinkType::tryFrom(
            (string) $this->validated('link_type', ''),
        );
    }

    /**
     * 公開範囲種別を取得する。
     *
     * @return ?ExternalLinkScopeType 処理結果。取得できない場合はnull
     */
    public function scopeType(): ?ExternalLinkScopeType
    {
        return ExternalLinkScopeType::tryFrom(
            (string) $this->validated('scope_type', ''),
        );
    }

    /**
     * 指定された状態を取得する。
     *
     * @return ?MasterStatus 処理結果。取得できない場合はnull
     */
    public function status(): ?MasterStatus
    {
        return MasterStatus::tryFrom(
            (string) $this->validated('status', ''),
        );
    }
}
