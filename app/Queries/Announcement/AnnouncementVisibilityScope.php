<?php

namespace App\Queries\Announcement;

use App\Enums\AnnouncementTargetType;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Database\Eloquent\Builder;

/**
 * ログインユーザーのロール・所属に応じてお知らせの公開対象条件を適用する。
 */
final class AnnouncementVisibilityScope
{
    /**
     * ユーザーが閲覧できるお知らせへ絞り込む。
     *
     * @param  Builder<Announcement>  $query  公開期間を適用済みのお知らせクエリ
     * @param  User  $user  閲覧可否を判定するログインユーザー
     * @return Builder<Announcement> 公開対象条件を適用したクエリ
     */
    public function apply(
        Builder $query,
        User $user,
    ): Builder {
        return match ($user->role) {
            UserRole::Admin => $query,
            UserRole::Teacher => $this->forTeacher($query, $user),
            UserRole::Student => $this->forStudent($query, $user),
        };
    }

    /**
     * 教員ロール・担当学年・担当クラスを公開対象に含むお知らせへ絞り込む。
     *
     * @param  Builder<Announcement>  $query  お知らせクエリ
     * @param  User  $user  閲覧する教員ユーザー
     * @return Builder<Announcement> 教員向け公開条件を適用したクエリ
     */
    private function forTeacher(
        Builder $query,
        User $user,
    ): Builder {
        [$gradeValues, $classGroupIds] = $this->teacherTargetValues($user);

        return $query->whereHas(
            'targets',
            static function (Builder $targetQuery) use (
                $gradeValues,
                $classGroupIds,
            ): void {
                $targetQuery->where(
                    static function (Builder $visibilityQuery) use (
                        $gradeValues,
                        $classGroupIds,
                    ): void {
                        $visibilityQuery
                            ->where(
                                'target_type',
                                AnnouncementTargetType::All->value,
                            )
                            ->orWhere(
                                static function (Builder $roleQuery): void {
                                    $roleQuery
                                        ->where(
                                            'target_type',
                                            AnnouncementTargetType::Role->value,
                                        )
                                        ->where(
                                            'target_value',
                                            UserRole::Teacher->value,
                                        );
                                },
                            );

                        if ($gradeValues !== []) {
                            $visibilityQuery->orWhere(
                                static function (Builder $gradeQuery) use ($gradeValues): void {
                                    $gradeQuery
                                        ->where(
                                            'target_type',
                                            AnnouncementTargetType::Grade->value,
                                        )
                                        ->whereIn('target_value', $gradeValues);
                                },
                            );
                        }

                        if ($classGroupIds !== []) {
                            $visibilityQuery->orWhere(
                                static function (Builder $classQuery) use ($classGroupIds): void {
                                    $classQuery
                                        ->where(
                                            'target_type',
                                            AnnouncementTargetType::ClassGroup->value,
                                        )
                                        ->whereIn('target_value', $classGroupIds);
                                },
                            );
                        }
                    },
                );
            },
        );
    }

    /**
     * 生徒ロール・所属学年・所属クラスを公開対象に含むお知らせへ絞り込む。
     *
     * @param  Builder<Announcement>  $query  お知らせクエリ
     * @param  User  $user  閲覧する生徒ユーザー
     * @return Builder<Announcement> 生徒向け公開条件を適用したクエリ
     */
    private function forStudent(
        Builder $query,
        User $user,
    ): Builder {
        $student = Student::query()
            ->where('user_id', $user->id)
            ->first();

        if ($student === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'targets',
            static function (Builder $targetQuery) use ($student): void {
                $targetQuery->where(
                    static function (Builder $visibilityQuery) use ($student): void {
                        $visibilityQuery
                            ->where(
                                'target_type',
                                AnnouncementTargetType::All->value,
                            )
                            ->orWhere(
                                static function (Builder $roleQuery): void {
                                    $roleQuery
                                        ->where(
                                            'target_type',
                                            AnnouncementTargetType::Role->value,
                                        )
                                        ->where(
                                            'target_value',
                                            UserRole::Student->value,
                                        );
                                },
                            )
                            ->orWhere(
                                static function (Builder $gradeQuery) use ($student): void {
                                    $gradeQuery
                                        ->where(
                                            'target_type',
                                            AnnouncementTargetType::Grade->value,
                                        )
                                        ->where(
                                            'target_value',
                                            $student->grade->value,
                                        );
                                },
                            )
                            ->orWhere(
                                static function (Builder $classQuery) use ($student): void {
                                    $classQuery
                                        ->where(
                                            'target_type',
                                            AnnouncementTargetType::ClassGroup->value,
                                        )
                                        ->where(
                                            'target_value',
                                            (string) $student->class_group_id,
                                        );
                                },
                            );
                    },
                );
            },
        );
    }

    /**
     * 教員が担当する学年値とクラスIDを取得する。
     *
     * @param  User  $user  対象の教員ユーザー
     * @return array{0: list<string>, 1: list<string>} 担当学年値と担当クラスID
     */
    private function teacherTargetValues(User $user): array
    {
        $teacher = Teacher::query()
            ->where('user_id', $user->id)
            ->first();

        if ($teacher === null) {
            return [[], []];
        }

        $courses = $teacher->courses()
            ->where('status', MasterStatus::Active->value)
            ->where('academic_year', AcademicYear::forDate())
            ->select(['grade', 'class_group_id'])
            ->get();

        $gradeValues = $courses
            ->pluck('grade')
            ->map(
                static fn (mixed $grade): string => $grade instanceof Grade
                    ? $grade->value
                    : (string) $grade,
            )
            ->unique()
            ->values()
            ->all();

        $classGroupIds = $courses
            ->pluck('class_group_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();

        return [
            array_values($gradeValues),
            array_values($classGroupIds),
        ];
    }
}
