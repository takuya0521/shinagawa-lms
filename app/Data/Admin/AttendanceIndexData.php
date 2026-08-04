<?php

namespace App\Data\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\Grade;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Support\Attendance\AttendanceStatistics;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 管理者向け出欠一覧画面へ渡す表示データを保持する。
 */
final readonly class AttendanceIndexData
{
    /**
     * 出欠一覧画面の表示データを生成する。
     *
     * @param  LengthAwarePaginator<int, AttendanceRecord>  $attendanceRecords  出欠記録一覧
     * @param  AttendanceStatistics  $statistics  指定条件の出欠集計
     * @param  Collection<int, Student>  $students  生徒選択肢
     * @param  Collection<int, Course>  $courses  授業選択肢
     * @param  Collection<int, ClassGroup>  $classGroups  クラス選択肢
     * @param  list<Grade>  $grades  学年選択肢
     * @param  list<AttendanceStatus>  $attendanceStatuses  出欠区分選択肢
     * @param  CarbonImmutable  $dateFrom  集計開始日
     * @param  CarbonImmutable  $dateTo  集計終了日
     * @param  int|null  $selectedStudentId  選択中の生徒ID
     * @param  int|null  $selectedCourseId  選択中の授業ID
     * @param  Grade|null  $selectedGrade  選択中の学年
     * @param  int|null  $selectedClassGroupId  選択中のクラスID
     * @param  AttendanceStatus|null  $selectedAttendanceStatus  選択中の出欠区分
     */
    public function __construct(
        public LengthAwarePaginator $attendanceRecords,
        public AttendanceStatistics $statistics,
        public Collection $students,
        public Collection $courses,
        public Collection $classGroups,
        public array $grades,
        public array $attendanceStatuses,
        public CarbonImmutable $dateFrom,
        public CarbonImmutable $dateTo,
        public ?int $selectedStudentId,
        public ?int $selectedCourseId,
        public ?Grade $selectedGrade,
        public ?int $selectedClassGroupId,
        public ?AttendanceStatus $selectedAttendanceStatus,
    ) {}

    /**
     * Bladeへ渡す連想配列へ変換する。
     *
     * @return array<string, mixed> 出欠一覧画面の表示データ
     */
    public function toViewData(): array
    {
        return [
            'attendanceRecords' => $this->attendanceRecords,
            'statistics' => $this->statistics,
            'students' => $this->students,
            'courses' => $this->courses,
            'classGroups' => $this->classGroups,
            'grades' => $this->grades,
            'attendanceStatuses' => $this->attendanceStatuses,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'selectedStudentId' => $this->selectedStudentId,
            'selectedCourseId' => $this->selectedCourseId,
            'selectedGrade' => $this->selectedGrade,
            'selectedClassGroupId' => $this->selectedClassGroupId,
            'selectedAttendanceStatus' => $this->selectedAttendanceStatus,
        ];
    }
}
