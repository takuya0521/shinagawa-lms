<?php

namespace App\Queries\Admin;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Models\ExternalLink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ExternalLinkListQuery
{
    /**
     * 管理者向けの外部リンク一覧を検索する。
     *
     * @param  string  $keyword  検索キーワード
     * @param  ?ExternalLinkType  $linkType  外部リンク種別
     * @param  ?ExternalLinkScopeType  $scopeType  公開範囲種別
     * @param  ?MasterStatus  $status  設定する状態
     * @return LengthAwarePaginator<int, ExternalLink>
     */
    public function execute(
        string $keyword,
        ?ExternalLinkType $linkType,
        ?ExternalLinkScopeType $scopeType,
        ?MasterStatus $status,
    ): LengthAwarePaginator {
        return ExternalLink::query()
            ->when(
                $keyword !== '',
                static function (Builder $query) use ($keyword): void {
                    $query->where(
                        static function (Builder $keywordQuery) use ($keyword): void {
                            $keywordQuery
                                ->whereLike('link_name', "%{$keyword}%")
                                ->orWhereLike('url', "%{$keyword}%");
                        },
                    );
                },
            )
            ->when(
                $linkType !== null,
                static fn (Builder $query): Builder => $query->where(
                    'link_type',
                    $linkType->value,
                ),
            )
            ->when(
                $scopeType !== null,
                static fn (Builder $query): Builder => $query->where(
                    'scope_type',
                    $scopeType->value,
                ),
            )
            ->when(
                $status !== null,
                static fn (Builder $query): Builder => $query->where(
                    'status',
                    $status->value,
                ),
            )
            ->orderBy('display_order')
            ->orderBy('link_name')
            ->paginate(20)
            ->withQueryString();
    }
}
