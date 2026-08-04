<?php

namespace App\Data\Admin;

use App\Enums\Grade;

/**
 * 担当教員設定一覧の検索条件を保持する。
 */
final readonly class CourseTeacherAssignmentFilters
{
    /**
     * 担当教員設定一覧の検索条件を生成する。
     *
     * @param  string  $keyword  授業名・科目・担当教員を対象とする検索語
     * @param  int|null  $academicYear  対象年度。全年度の場合はnull
     * @param  Grade|null  $grade  対象学年
     * @param  int|null  $classGroupId  対象クラスID
     * @param  int|null  $subjectId  対象科目ID
     * @param  string|null  $assignmentStatus  担当状況。assignedまたはunassigned
     */
    public function __construct(
        public string $keyword,
        public ?int $academicYear,
        public ?Grade $grade,
        public ?int $classGroupId,
        public ?int $subjectId,
        public ?string $assignmentStatus,
    ) {}
}
