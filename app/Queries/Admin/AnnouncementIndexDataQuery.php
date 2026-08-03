<?php

namespace App\Queries\Admin;

use App\Data\Admin\AnnouncementIndexData;
use App\Data\Admin\AnnouncementIndexFilters;
use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use Illuminate\Database\Eloquent\Collection;

/**
 * 管理者向けお知らせ一覧画面の表示データを取得する。
 */
final class AnnouncementIndexDataQuery
{
    /**
     * 表示データ取得処理を生成する。
     *
     * @param AnnouncementListQuery $listQuery お知らせ一覧の検索処理
     */
    public function __construct(
        private readonly AnnouncementListQuery $listQuery,
    ) {}

    /**
     * 管理者向けお知らせ一覧画面の表示データを取得する。
     *
     * @param AnnouncementIndexFilters $filters 検索条件
     * @return AnnouncementIndexData お知らせ一覧画面の表示データ
     */
    public function execute(
        AnnouncementIndexFilters $filters,
    ): AnnouncementIndexData {
        $classGroups = ClassGroup::query()
            ->orderBy('class_code')
            ->get();

        return new AnnouncementIndexData(
            announcements: $this->listQuery->execute($filters),
            noticeTypes: AnnouncementNoticeType::cases(),
            statuses: AnnouncementStatus::cases(),
            targetOptions: $this->targetOptions($classGroups),
            classGroupNames: $classGroups
                ->pluck('class_name', 'id')
                ->mapWithKeys(
                    static fn (mixed $name, mixed $id): array => [
                        (int) $id => (string) $name,
                    ],
                )
                ->all(),
            filters: $filters,
        );
    }

    /**
     * 一覧検索用の公開対象選択肢を生成する。
     *
     * @param Collection<int, ClassGroup> $classGroups クラス一覧
     * @return array<string, string> 公開対象の値と表示名
     */
    private function targetOptions(Collection $classGroups): array
    {
        $options = [
            AnnouncementTargetType::All->value => '全員',
            AnnouncementTargetType::Role->value.':'.UserRole::Teacher->value => 'ロール：教員',
            AnnouncementTargetType::Role->value.':'.UserRole::Student->value => 'ロール：生徒',
        ];

        foreach (Grade::cases() as $grade) {
            $options[
                AnnouncementTargetType::Grade->value.':'.$grade->value
            ] = '学年：'.$grade->label();
        }

        foreach ($classGroups as $classGroup) {
            $options[
                AnnouncementTargetType::ClassGroup->value.':'.$classGroup->id
            ] = 'クラス：'.$classGroup->class_name;
        }

        return $options;
    }
}
