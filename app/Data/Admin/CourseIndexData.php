<?php

namespace App\Data\Admin;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * 授業一覧画面へ渡す表示データを保持する。
 */
final readonly class CourseIndexData
{
    /**
     * 授業一覧画面の表示データを生成する。
     *
     * @param LengthAwarePaginator<int, Course> $courses 授業一覧
     * @param Collection<int, int> $academicYears 年度選択肢
     * @param list<Grade> $grades 学年選択肢
     * @param EloquentCollection<int, ClassGroup> $classGroups クラス選択肢
     * @param list<MasterStatus> $statuses 状態選択肢
     * @param string $keyword キーワード
     * @param int|null $selectedAcademicYear 選択中の年度
     * @param Grade|null $selectedGrade 選択中の学年
     * @param int|null $selectedClassGroupId 選択中のクラスID
     * @param MasterStatus|null $selectedStatus 選択中の状態
     */
    public function __construct(
        public LengthAwarePaginator $courses,
        public Collection $academicYears,
        public array $grades,
        public EloquentCollection $classGroups,
        public array $statuses,
        public string $keyword,
        public ?int $selectedAcademicYear,
        public ?Grade $selectedGrade,
        public ?int $selectedClassGroupId,
        public ?MasterStatus $selectedStatus,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 授業一覧画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            'courses' => $this->courses,
            'academicYears' => $this->academicYears,
            'grades' => $this->grades,
            'classGroups' => $this->classGroups,
            'statuses' => $this->statuses,
            'keyword' => $this->keyword,
            'selectedAcademicYear' => $this->selectedAcademicYear,
            'selectedGrade' => $this->selectedGrade?->value,
            'selectedClassGroupId' => $this->selectedClassGroupId,
            'selectedStatus' => $this->selectedStatus?->value,
        ];
    }
}
