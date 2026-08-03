<?php

namespace App\Data\Admin;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

/**
 * 外部リンク登録・編集画面で使用する選択肢を保持する。
 */
final readonly class ExternalLinkFormData
{
    /**
     * 外部リンクフォームの表示データを生成する。
     *
     * @param list<ExternalLinkType> $linkTypes 外部リンク種別選択肢
     * @param list<ExternalLinkScopeType> $scopeTypes 公開範囲種別選択肢
     * @param list<MasterStatus> $statuses 状態選択肢
     * @param list<UserRole> $roles ロール選択肢
     * @param Collection<int, ClassGroup> $classGroups クラス選択肢
     * @param Collection<int, Course> $courses 授業選択肢
     * @param Collection<int, Student> $students 生徒選択肢
     */
    public function __construct(
        public array $linkTypes,
        public array $scopeTypes,
        public array $statuses,
        public array $roles,
        public Collection $classGroups,
        public Collection $courses,
        public Collection $students,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 外部リンクフォームの表示データ
     */
    public function toViewData(): array
    {
        return [
            'linkTypes' => $this->linkTypes,
            'scopeTypes' => $this->scopeTypes,
            'statuses' => $this->statuses,
            'roles' => $this->roles,
            'classGroups' => $this->classGroups,
            'courses' => $this->courses,
            'students' => $this->students,
        ];
    }
}
