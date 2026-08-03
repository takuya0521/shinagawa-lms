<?php

namespace App\Data\Admin;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * 授業登録・編集画面へ渡す選択肢を保持する。
 */
final readonly class CourseFormData
{
    /**
     * 授業フォームの表示データを生成する。
     *
     * @param Collection<int, int> $academicYears 年度選択肢
     * @param list<Grade> $grades 学年選択肢
     * @param EloquentCollection<int, Subject> $subjects 科目選択肢
     * @param EloquentCollection<int, ClassGroup> $classGroups クラス選択肢
     * @param EloquentCollection<int, Teacher> $teachers 担当教員選択肢
     * @param list<MasterStatus> $statuses 状態選択肢
     */
    public function __construct(
        public Collection $academicYears,
        public array $grades,
        public EloquentCollection $subjects,
        public EloquentCollection $classGroups,
        public EloquentCollection $teachers,
        public array $statuses,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 授業フォームの表示データ
     */
    public function toViewData(): array
    {
        return [
            'academicYears' => $this->academicYears,
            'grades' => $this->grades,
            'subjects' => $this->subjects,
            'classGroups' => $this->classGroups,
            'teachers' => $this->teachers,
            'statuses' => $this->statuses,
        ];
    }
}
