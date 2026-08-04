<?php

namespace App\Data\Admin;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * 管理者向け面談記録フォームで使用する選択肢を保持する。
 */
final readonly class InterviewFormData
{
    /**
     * 面談記録フォームの表示データを生成する。
     *
     * @param  EloquentCollection<int, Student>  $students  生徒選択肢
     * @param  EloquentCollection<int, Teacher>  $teachers  教員選択肢
     * @param  Collection<int, string>  $interviewTypes  面談種別選択肢
     */
    public function __construct(
        public EloquentCollection $students,
        public EloquentCollection $teachers,
        public Collection $interviewTypes,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 面談記録フォームの表示データ
     */
    public function toViewData(): array
    {
        return [
            'students' => $this->students,
            'teachers' => $this->teachers,
            'interviewTypes' => $this->interviewTypes,
        ];
    }
}
