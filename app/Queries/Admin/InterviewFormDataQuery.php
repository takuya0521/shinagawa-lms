<?php

namespace App\Queries\Admin;

use App\Data\Admin\InterviewFormData;
use App\Models\Student;
use App\Models\Teacher;
use App\Queries\Interview\InterviewTypeOptionsQuery;

/**
 * 管理者向け面談記録フォームの表示データを取得する。
 */
final readonly class InterviewFormDataQuery
{
    /**
     * 面談種別選択肢の取得処理を受け取る。
     *
     * @param InterviewTypeOptionsQuery $interviewTypeOptionsQuery 面談種別選択肢の取得処理
     */
    public function __construct(
        private InterviewTypeOptionsQuery $interviewTypeOptionsQuery,
    ) {}

    /**
     * 面談記録フォームで使用する選択肢を取得する。
     *
     * @return InterviewFormData 面談記録フォームの表示データ
     */
    public function execute(): InterviewFormData
    {
        return new InterviewFormData(
            students: Student::query()
                ->with('classGroup')
                ->orderByRaw('student_no IS NULL')
                ->orderBy('student_no')
                ->orderBy('student_name')
                ->get(),
            teachers: Teacher::query()
                ->with('user')
                ->join('users', 'users.id', '=', 'teachers.user_id')
                ->select('teachers.*')
                ->orderBy('users.name')
                ->get(),
            interviewTypes: $this->interviewTypeOptionsQuery->execute(),
        );
    }
}
