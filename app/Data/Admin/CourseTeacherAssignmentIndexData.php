<?php

namespace App\Data\Admin;

use App\Enums\Grade;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * 担当教員設定一覧画面へ渡す表示データを保持する。
 */
final readonly class CourseTeacherAssignmentIndexData
{
    /**
     * 担当教員設定一覧画面の表示データを生成する。
     *
     * @param  LengthAwarePaginator<int, Course>  $courses  担当教員設定対象の授業一覧
     * @param  EloquentCollection<int, Teacher>  $teachers  選択可能な教員一覧
     * @param  Collection<int, int>  $academicYears  年度選択肢
     * @param  list<Grade>  $grades  学年選択肢
     * @param  EloquentCollection<int, ClassGroup>  $classGroups  クラス選択肢
     * @param  EloquentCollection<int, Subject>  $subjects  科目選択肢
     * @param  CourseTeacherAssignmentFilters  $filters  選択中の検索条件
     */
    public function __construct(
        public LengthAwarePaginator $courses,
        public EloquentCollection $teachers,
        public Collection $academicYears,
        public array $grades,
        public EloquentCollection $classGroups,
        public EloquentCollection $subjects,
        public CourseTeacherAssignmentFilters $filters,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 担当教員設定一覧画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            'courses' => $this->courses,
            'teachers' => $this->teachers,
            'academicYears' => $this->academicYears,
            'grades' => $this->grades,
            'classGroups' => $this->classGroups,
            'subjects' => $this->subjects,
            'keyword' => $this->filters->keyword,
            'selectedAcademicYear' => $this->filters->academicYear,
            'selectedGrade' => $this->filters->grade?->value,
            'selectedClassGroupId' => $this->filters->classGroupId,
            'selectedSubjectId' => $this->filters->subjectId,
            'selectedAssignmentStatus' => $this->filters->assignmentStatus,
        ];
    }
}
