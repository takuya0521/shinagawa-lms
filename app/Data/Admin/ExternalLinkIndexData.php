<?php

namespace App\Data\Admin;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Models\ExternalLink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 外部リンク一覧画面へ渡す表示データを保持する。
 */
final readonly class ExternalLinkIndexData
{
    /**
     * 外部リンク一覧画面の表示データを生成する。
     *
     * @param  LengthAwarePaginator<int, ExternalLink>  $externalLinks  外部リンク一覧
     * @param  list<ExternalLinkType>  $linkTypes  外部リンク種別選択肢
     * @param  list<ExternalLinkScopeType>  $scopeTypes  公開範囲種別選択肢
     * @param  list<MasterStatus>  $statuses  状態選択肢
     * @param  array<string, array<int, string>>  $scopeLabels  公開範囲の表示名
     * @param  string  $keyword  検索キーワード
     * @param  ExternalLinkType|null  $selectedLinkType  選択中の外部リンク種別
     * @param  ExternalLinkScopeType|null  $selectedScopeType  選択中の公開範囲種別
     * @param  MasterStatus|null  $selectedStatus  選択中の状態
     */
    public function __construct(
        public LengthAwarePaginator $externalLinks,
        public array $linkTypes,
        public array $scopeTypes,
        public array $statuses,
        public array $scopeLabels,
        public string $keyword,
        public ?ExternalLinkType $selectedLinkType,
        public ?ExternalLinkScopeType $selectedScopeType,
        public ?MasterStatus $selectedStatus,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 外部リンク一覧画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            'externalLinks' => $this->externalLinks,
            'linkTypes' => $this->linkTypes,
            'scopeTypes' => $this->scopeTypes,
            'statuses' => $this->statuses,
            'scopeLabels' => $this->scopeLabels,
            'keyword' => $this->keyword,
            'selectedLinkType' => $this->selectedLinkType?->value,
            'selectedScopeType' => $this->selectedScopeType?->value,
            'selectedStatus' => $this->selectedStatus?->value,
        ];
    }
}
