<?php

namespace App\Queries\Admin;

use App\Data\Admin\AnnouncementIndexFilters;
use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * 管理者向けお知らせ一覧を検索する。
 */
final class AnnouncementListQuery
{
    /**
     * 管理者向けお知らせ一覧を取得する。
     *
     * @param AnnouncementIndexFilters $filters 検索条件
     * @return LengthAwarePaginator<int, Announcement> 1ページ20件のお知らせ一覧
     */
    public function execute(
        AnnouncementIndexFilters $filters,
    ): LengthAwarePaginator {
        $query = Announcement::query()
            ->with(['targets', 'creator', 'updater']);

        if ($filters->keyword !== '') {
            $this->applyKeywordFilter($query, $filters->keyword);
        }

        if ($filters->target !== null) {
            $this->applyTargetFilter($query, $filters->target);
        }

        return $query
            ->when(
                $filters->noticeType !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    'notice_type',
                    $filters->noticeType->value,
                ),
            )
            ->when(
                $filters->status !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    'status',
                    $filters->status->value,
                ),
            )
            ->when(
                $filters->importantOnly === true,
                static fn (Builder $builder): Builder => $builder->where(
                    'is_important',
                    true,
                ),
            )
            ->when(
                $filters->publishFrom !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    static function (Builder $periodQuery) use ($filters): void {
                        $periodQuery
                            ->whereNull('publish_end_at')
                            ->orWhereDate(
                                'publish_end_at',
                                '>=',
                                $filters->publishFrom,
                            );
                    },
                ),
            )
            ->when(
                $filters->publishTo !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    static function (Builder $periodQuery) use ($filters): void {
                        $periodQuery
                            ->whereNull('publish_start_at')
                            ->orWhereDate(
                                'publish_start_at',
                                '<=',
                                $filters->publishTo,
                            );
                    },
                ),
            )
            ->orderByDesc('is_important')
            ->orderByDesc('publish_start_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * タイトルと本文へキーワード条件を適用する。
     *
     * @param Builder<Announcement> $query お知らせ検索クエリ
     * @param string $keyword 検索語
     *
     * @return void 戻り値なし
     */
    private function applyKeywordFilter(
        Builder $query,
        string $keyword,
    ): void {
        $query->where(
            static function (Builder $keywordQuery) use ($keyword): void {
                $keywordQuery
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('body', 'like', "%{$keyword}%");
            },
        );
    }

    /**
     * 公開対象条件を適用する。
     *
     * @param Builder<Announcement> $query お知らせ検索クエリ
     * @param string $target 公開対象を表す「種別:値」形式の文字列
     *
     * @return void 戻り値なし
     */
    private function applyTargetFilter(
        Builder $query,
        string $target,
    ): void {
        [$targetType, $targetValue] = array_pad(
            explode(':', $target, 2),
            2,
            null,
        );

        $query->whereHas(
            'targets',
            static function (Builder $targetQuery) use (
                $targetType,
                $targetValue,
            ): void {
                $targetQuery->where('target_type', $targetType);

                if ($targetValue !== null && $targetValue !== '') {
                    $targetQuery->where('target_value', $targetValue);
                }
            },
        );
    }
}
