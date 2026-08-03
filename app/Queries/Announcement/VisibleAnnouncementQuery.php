<?php

namespace App\Queries\Announcement;

use App\Enums\AnnouncementNoticeType;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ログインユーザーが閲覧できる掲載中のお知らせを検索する。
 */
final class VisibleAnnouncementQuery
{
    /**
     * 閲覧範囲を適用する処理を受け取る。
     *
     * @param AnnouncementVisibilityScope $visibilityScope ユーザー別の公開対象条件を適用する処理
     */
    public function __construct(
        private readonly AnnouncementVisibilityScope $visibilityScope,
    ) {}

    /**
     * ログインユーザーが閲覧できる掲載中のお知らせを取得する。
     *
     * @param User $user 閲覧するログインユーザー
     * @param string $keyword タイトル・本文を対象とする検索語
     * @param AnnouncementNoticeType|null $noticeType お知らせ種別
     * @param bool|null $importantOnly 重要なお知らせだけを表示するか
     * @param int $perPage 1ページの表示件数
     * @return LengthAwarePaginator<int, Announcement> 閲覧可能なお知らせ一覧
     */
    public function paginate(
        User $user,
        string $keyword,
        ?AnnouncementNoticeType $noticeType,
        ?bool $importantOnly,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = $this->baseQuery($user);

        if ($keyword !== '') {
            $this->applyKeywordFilter($query, $keyword);
        }

        return $query
            ->when(
                $noticeType !== null,
                static fn (Builder $builder): Builder => $builder->where(
                    'notice_type',
                    $noticeType->value,
                ),
            )
            ->when(
                $importantOnly === true,
                static fn (Builder $builder): Builder => $builder->where(
                    'is_important',
                    true,
                ),
            )
            ->orderByDesc('is_important')
            ->orderByDesc('publish_start_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * ダッシュボード表示用の最新お知らせを取得する。
     *
     * @param User $user 閲覧するログインユーザー
     * @param int $limit 取得上限件数
     * @return Collection<int, Announcement> 閲覧可能な最新のお知らせ一覧
     */
    public function latest(
        User $user,
        int $limit,
    ): Collection {
        return $this->baseQuery($user)
            ->orderByDesc('is_important')
            ->orderByDesc('publish_start_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * ログインユーザーが閲覧可能な単一のお知らせを取得する。
     *
     * @param User $user 閲覧するログインユーザー
     * @param int $announcementId 取得するお知らせID
     * @return Announcement 閲覧可能なお知らせ
     */
    public function findOrFail(
        User $user,
        int $announcementId,
    ): Announcement {
        return $this->baseQuery($user)
            ->whereKey($announcementId)
            ->firstOrFail();
    }

    /**
     * 公開期間とユーザー別公開対象条件を適用した基礎クエリを生成する。
     *
     * @param User $user 閲覧するログインユーザー
     * @return Builder<Announcement> 閲覧可能なお知らせの基礎クエリ
     */
    private function baseQuery(User $user): Builder
    {
        $query = Announcement::query()
            ->with(['targets', 'creator'])
            ->publishedAt(now());

        return $this->visibilityScope->apply($query, $user);
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
}
