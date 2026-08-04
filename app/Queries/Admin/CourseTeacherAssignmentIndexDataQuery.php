<?php

namespace App\Queries\Admin;

use App\Data\Admin\CourseTeacherAssignmentFilters;
use App\Data\Admin\CourseTeacherAssignmentIndexData;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * 担当教員設定一覧画面の表示データを取得する。
 */
final class CourseTeacherAssignmentIndexDataQuery
{
    /**
     * 表示データ取得処理を生成する。
     *
     * @param  CourseTeacherAssignmentListQuery  $listQuery  授業一覧の検索処理
     */
    public function __construct(
        private readonly CourseTeacherAssignmentListQuery $listQuery,
    ) {}

    /**
     * 担当教員設定一覧画面の表示データを取得する。
     *
     * @param  CourseTeacherAssignmentFilters  $filters  検索条件
     * @return CourseTeacherAssignmentIndexData 担当教員設定一覧画面の表示データ
     */
    public function execute(
        CourseTeacherAssignmentFilters $filters,
    ): CourseTeacherAssignmentIndexData {
        return new CourseTeacherAssignmentIndexData(
            courses: $this->listQuery->execute(
                $filters->keyword,
                $filters->academicYear,
                $filters->grade,
                $filters->classGroupId,
                $filters->subjectId,
                $filters->assignmentStatus,
            ),
            teachers: $this->availableTeachers(),
            academicYears: $this->academicYears($filters->academicYear),
            grades: Grade::cases(),
            classGroups: ClassGroup::query()
                ->orderBy('class_code')
                ->get(),
            subjects: Subject::query()
                ->orderBy('subject_code')
                ->get(),
            filters: $filters,
        );
    }

    /**
     * 担当として選択できる有効な教員を取得する。
     *
     * @return EloquentCollection<int, Teacher> 選択可能な教員一覧
     */
    private function availableTeachers(): EloquentCollection
    {
        return Teacher::query()
            ->select('teachers.*')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->where('teachers.status', MasterStatus::Active->value)
            ->where('users.role', UserRole::Teacher->value)
            ->where('users.status', UserStatus::Active->value)
            ->with('user')
            ->orderBy('users.name')
            ->get();
    }

    /**
     * 既存授業年度と現在年度周辺をまとめた年度選択肢を取得する。
     *
     * @param  int|null  $selectedAcademicYear  現在選択中の年度
     * @return Collection<int, int> 降順に並べた年度一覧
     */
    private function academicYears(
        ?int $selectedAcademicYear,
    ): Collection {
        $years = Course::query()
            ->select('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->map(static fn (mixed $year): int => (int) $year)
            ->merge(range(now()->year + 1, now()->year - 2));

        if ($selectedAcademicYear !== null) {
            $years->push($selectedAcademicYear);
        }

        return $years
            ->unique()
            ->sortDesc()
            ->values();
    }
}
