<?php

namespace App\Queries\Admin;

use App\Data\Admin\CourseIndexData;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * 授業一覧画面の表示データを取得する。
 */
final readonly class CourseIndexDataQuery
{
    /**
     * 授業一覧検索処理を受け取る。
     *
     * @param  CourseListQuery  $courseListQuery  授業一覧の検索処理
     */
    public function __construct(
        private CourseListQuery $courseListQuery,
    ) {}

    /**
     * 指定された検索条件で授業一覧画面の表示データを取得する。
     *
     * @param  string  $keyword  キーワード
     * @param  int|null  $academicYear  年度
     * @param  Grade|null  $grade  学年
     * @param  int|null  $classGroupId  クラスID
     * @param  MasterStatus|null  $status  状態
     * @return CourseIndexData 授業一覧画面の表示データ
     */
    public function execute(
        string $keyword,
        ?int $academicYear,
        ?Grade $grade,
        ?int $classGroupId,
        ?MasterStatus $status,
    ): CourseIndexData {
        return new CourseIndexData(
            courses: $this->courseListQuery->execute(
                $keyword,
                $academicYear,
                $grade,
                $classGroupId,
                $status,
            ),
            academicYears: $this->academicYears(),
            grades: Grade::cases(),
            classGroups: ClassGroup::query()
                ->orderBy('class_code')
                ->get(),
            statuses: MasterStatus::cases(),
            keyword: $keyword,
            selectedAcademicYear: $academicYear,
            selectedGrade: $grade,
            selectedClassGroupId: $classGroupId,
            selectedStatus: $status,
        );
    }

    /**
     * DB登録済み年度と現在年度周辺をまとめた年度選択肢を返す。
     *
     * @return Collection<int, int> 降順の年度選択肢
     */
    private function academicYears(): Collection
    {
        return Course::query()
            ->select('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->map(static fn (mixed $year): int => (int) $year)
            ->merge(range(now()->year + 1, now()->year - 1))
            ->unique()
            ->sortDesc()
            ->values();
    }
}
