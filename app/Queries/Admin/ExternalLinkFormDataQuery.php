<?php

namespace App\Queries\Admin;

use App\Data\Admin\ExternalLinkFormData;
use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;

/**
 * 外部リンク登録・編集画面の表示データを取得する。
 */
final class ExternalLinkFormDataQuery
{
    /**
     * 外部リンクフォームで使用する選択肢を取得する。
     *
     * @return ExternalLinkFormData 外部リンクフォームの表示データ
     */
    public function execute(): ExternalLinkFormData
    {
        return new ExternalLinkFormData(
            linkTypes: ExternalLinkType::cases(),
            scopeTypes: ExternalLinkScopeType::cases(),
            statuses: MasterStatus::cases(),
            roles: UserRole::cases(),
            classGroups: ClassGroup::query()
                ->orderBy('class_code')
                ->get(),
            courses: Course::query()
                ->with([
                    'subject',
                    'classGroup',
                ])
                ->orderByDesc('academic_year')
                ->orderBy('course_name')
                ->get(),
            students: Student::query()
                ->with('classGroup')
                ->orderByRaw('student_no IS NULL')
                ->orderBy('student_no')
                ->orderBy('student_name')
                ->get(),
        );
    }
}
