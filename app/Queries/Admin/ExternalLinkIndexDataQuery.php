<?php

namespace App\Queries\Admin;

use App\Data\Admin\ExternalLinkIndexData;
use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;

/**
 * 外部リンク一覧画面の表示データを取得する。
 */
final readonly class ExternalLinkIndexDataQuery
{
    /**
     * 外部リンク一覧の検索処理を受け取る。
     *
     * @param ExternalLinkListQuery $listQuery 外部リンク一覧の検索処理
     */
    public function __construct(
        private ExternalLinkListQuery $listQuery,
    ) {}

    /**
     * 外部リンク一覧画面の表示データを取得する。
     *
     * @param string $keyword 検索キーワード
     * @param ExternalLinkType|null $linkType 外部リンク種別
     * @param ExternalLinkScopeType|null $scopeType 公開範囲種別
     * @param MasterStatus|null $status 状態
     * @return ExternalLinkIndexData 外部リンク一覧画面の表示データ
     */
    public function execute(
        string $keyword,
        ?ExternalLinkType $linkType,
        ?ExternalLinkScopeType $scopeType,
        ?MasterStatus $status,
    ): ExternalLinkIndexData {
        return new ExternalLinkIndexData(
            externalLinks: $this->listQuery->execute(
                $keyword,
                $linkType,
                $scopeType,
                $status,
            ),
            linkTypes: ExternalLinkType::cases(),
            scopeTypes: ExternalLinkScopeType::cases(),
            statuses: MasterStatus::cases(),
            scopeLabels: $this->scopeLabels(),
            keyword: $keyword,
            selectedLinkType: $linkType,
            selectedScopeType: $scopeType,
            selectedStatus: $status,
        );
    }

    /**
     * 公開範囲種別ごとの表示名を取得する。
     *
     * @return array<string, array<int, string>> 公開範囲の表示名
     */
    private function scopeLabels(): array
    {
        return [
            ExternalLinkScopeType::Role->value => collect(UserRole::cases())
                ->mapWithKeys(
                    static fn (UserRole $role): array => [
                        $role->scopeId() => $role->label(),
                    ],
                )
                ->all(),
            ExternalLinkScopeType::ClassGroup->value => ClassGroup::query()
                ->pluck('class_name', 'id')
                ->all(),
            ExternalLinkScopeType::Course->value => Course::query()
                ->pluck('course_name', 'id')
                ->all(),
            ExternalLinkScopeType::Student->value => Student::query()
                ->pluck('student_name', 'id')
                ->all(),
        ];
    }
}
