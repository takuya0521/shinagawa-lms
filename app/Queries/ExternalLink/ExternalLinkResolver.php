<?php

namespace App\Queries\ExternalLink;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\ExternalLink;
use App\Models\Student;
use App\Support\AcademicYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * 利用者の所属条件に応じて外部リンクを解決する。
 */
final class ExternalLinkResolver
{
    private const SCOPE_PRIORITY_SQL = "CASE scope_type\n"
        ."        WHEN 'student' THEN 1\n"
        ."        WHEN 'course' THEN 2\n"
        ."        WHEN 'class_group' THEN 3\n"
        ."        WHEN 'role' THEN 4\n"
        ."        ELSE 5\n"
        .'    END';

    /**
     * 生徒本人が利用できる外部リンクを解決する。
     *
     * @param  Student  $student  対象生徒
     * @param  ExternalLinkType|null  $linkType  指定時は外部リンク種別で絞り込む
     * @return Collection<int, ExternalLink> 優先順位順の外部リンク一覧
     */
    public function forStudent(
        Student $student,
        ?ExternalLinkType $linkType = null,
    ): Collection {
        $courseIds = $this->courseIdsForStudent($student);
        $query = $this->activeLinkQuery($linkType);

        $this->applyStudentScopes($query, $student, $courseIds);

        return $query
            ->orderByRaw(self::SCOPE_PRIORITY_SQL)
            ->orderBy('display_order')
            ->orderBy('link_name')
            ->get();
    }

    /**
     * 生徒が対象となる有効な授業IDを取得する。
     *
     * @param  Student  $student  対象生徒
     * @return list<int> 対象授業ID
     */
    private function courseIdsForStudent(Student $student): array
    {
        $courseIds = Course::query()
            ->active()
            ->forTarget(
                AcademicYear::forDate(),
                $student->grade,
                $student->class_group_id,
            )
            ->pluck('id')
            ->all();

        return array_values(
            array_map(
                static fn (mixed $id): int => (int) $id,
                $courseIds,
            ),
        );
    }

    /**
     * 有効な外部リンクの基礎クエリを作成する。
     *
     * @param  ExternalLinkType|null  $linkType  外部リンク種別
     * @return Builder<ExternalLink> 外部リンクの基礎クエリ
     */
    private function activeLinkQuery(
        ?ExternalLinkType $linkType,
    ): Builder {
        return ExternalLink::query()
            ->where('status', MasterStatus::Active->value)
            ->when(
                $linkType !== null,
                static fn (Builder $query): Builder => $query->where(
                    'link_type',
                    $linkType?->value,
                ),
            );
    }

    /**
     * 生徒本人、所属クラス、対象授業、生徒ロール、全体公開の条件を適用する。
     *
     * @param  Builder<ExternalLink>  $query  外部リンククエリ
     * @param  Student  $student  対象生徒
     * @param  list<int>  $courseIds  対象授業ID
     * @return void 戻り値なし
     */
    private function applyStudentScopes(
        Builder $query,
        Student $student,
        array $courseIds,
    ): void {
        $query->where(
            static function (Builder $scopeQuery) use (
                $student,
                $courseIds,
            ): void {
                $scopeQuery
                    ->where(
                        'scope_type',
                        ExternalLinkScopeType::Global->value,
                    )
                    ->orWhere(
                        static function (Builder $roleQuery): void {
                            $roleQuery
                                ->where(
                                    'scope_type',
                                    ExternalLinkScopeType::Role->value,
                                )
                                ->where(
                                    'scope_id',
                                    UserRole::Student->scopeId(),
                                );
                        },
                    )
                    ->orWhere(
                        static function (Builder $classQuery) use ($student): void {
                            $classQuery
                                ->where(
                                    'scope_type',
                                    ExternalLinkScopeType::ClassGroup->value,
                                )
                                ->where(
                                    'scope_id',
                                    $student->class_group_id,
                                );
                        },
                    )
                    ->orWhere(
                        static function (Builder $studentQuery) use ($student): void {
                            $studentQuery
                                ->where(
                                    'scope_type',
                                    ExternalLinkScopeType::Student->value,
                                )
                                ->where('scope_id', $student->id);
                        },
                    );

                if ($courseIds !== []) {
                    $scopeQuery->orWhere(
                        static function (Builder $courseQuery) use ($courseIds): void {
                            $courseQuery
                                ->where(
                                    'scope_type',
                                    ExternalLinkScopeType::Course->value,
                                )
                                ->whereIn('scope_id', $courseIds);
                        },
                    );
                }
            },
        );
    }
}
