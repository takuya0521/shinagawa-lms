<?php

namespace App\Queries\Admin;

use App\Data\Admin\CourseFormData;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * 授業登録・編集画面の選択肢を取得する。
 */
final class CourseFormDataQuery
{
    /**
     * 授業登録・編集画面の表示データを取得する。
     *
     * 無効化済みの現在値は編集時に限って選択肢へ残す。
     *
     * @param Course|null $course 編集対象の授業。登録時はnull
     * @return CourseFormData 授業フォームの表示データ
     */
    public function execute(?Course $course = null): CourseFormData
    {
        return new CourseFormData(
            academicYears: $this->academicYears($course?->academic_year),
            grades: Grade::cases(),
            subjects: $this->subjects($course?->subject_id),
            classGroups: ClassGroup::query()
                ->selectable($course?->class_group_id)
                ->orderBy('class_code')
                ->get(),
            teachers: $this->teachers($course?->teacher_id),
            statuses: MasterStatus::cases(),
        );
    }

    /**
     * DB登録済み年度と現在年度周辺をまとめた年度選択肢を返す。
     *
     * @param int|null $currentAcademicYear 編集対象に設定されている年度
     * @return Collection<int, int> 降順の年度選択肢
     */
    private function academicYears(?int $currentAcademicYear): Collection
    {
        $years = Course::query()
            ->select('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->map(static fn (mixed $year): int => (int) $year)
            ->merge(range(now()->year + 1, now()->year - 1));

        if ($currentAcademicYear !== null) {
            $years->push($currentAcademicYear);
        }

        return $years->unique()->sortDesc()->values();
    }

    /**
     * 有効な科目と編集前に選択されていた科目を返す。
     *
     * @param int|null $currentSubjectId 編集前の科目ID
     * @return \Illuminate\Database\Eloquent\Collection<int, Subject> 科目選択肢
     */
    private function subjects(
        ?int $currentSubjectId,
    ): \Illuminate\Database\Eloquent\Collection {
        return Subject::query()
            ->where(
                static function (Builder $query) use ($currentSubjectId): void {
                    $query->where('status', MasterStatus::Active->value);

                    if ($currentSubjectId !== null) {
                        $query->orWhere('id', $currentSubjectId);
                    }
                },
            )
            ->orderBy('subject_code')
            ->get();
    }

    /**
     * 利用可能な教員と編集前に選択されていた教員を返す。
     *
     * @param int|null $currentTeacherId 編集前の担当教員ID
     * @return \Illuminate\Database\Eloquent\Collection<int, Teacher> 担当教員選択肢
     */
    private function teachers(
        ?int $currentTeacherId,
    ): \Illuminate\Database\Eloquent\Collection {
        return Teacher::query()
            ->select('teachers.*')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->where('users.role', UserRole::Teacher->value)
            ->where(
                static function (Builder $query) use ($currentTeacherId): void {
                    $query->where(
                        static function (Builder $activeQuery): void {
                            $activeQuery
                                ->where(
                                    'teachers.status',
                                    MasterStatus::Active->value,
                                )
                                ->where(
                                    'users.status',
                                    UserStatus::Active->value,
                                );
                        },
                    );

                    if ($currentTeacherId !== null) {
                        $query->orWhere('teachers.id', $currentTeacherId);
                    }
                },
            )
            ->with('user')
            ->orderBy('users.name')
            ->get();
    }
}
